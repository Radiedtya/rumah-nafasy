<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailOtp;
use App\Models\EmailVerificationOtp;
use App\Models\PendingRegistration;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rina Wijaya',
            'email' => 'rina@example.com',
            'phone' => '081234567890',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ], $overrides);
    }

    /**
     * Registrasi + kembalikan baris pending (kandidat akun) — bukan User.
     * Email pendaftar BARU muncul di tabel users HANYA setelah OTP benar.
     */
    private function registerPending(array $overrides = []): PendingRegistration
    {
        Mail::fake();
        $payload = $this->registerPayload($overrides);

        $this->postJson('/api/v1/auth/register', $payload)->assertCreated();

        return PendingRegistration::where('email', $payload['email'])->firstOrFail();
    }

    public function test_register_tidak_menulis_email_ke_tabel_users_sebelum_otp(): void
    {
        Mail::fake();

        $res = $this->postJson('/api/v1/auth/register', $this->registerPayload());

        $res->assertCreated()
            ->assertJsonPath('data.requires_verification', true)
            ->assertJsonStructure(['data' => ['verify_handle']])
            ->assertJsonMissing(['token']);

        // inti perbaikan: TIDAK ada baris users untuk email pendaftar baru
        $this->assertDatabaseMissing('users', ['email' => 'rina@example.com']);

        // kandidat akun tersimpan di pending_registrations
        $this->assertDatabaseHas('pending_registrations', ['email' => 'rina@example.com']);

        Mail::assertQueued(VerifyEmailOtp::class, 1);
    }

    public function test_register_menolak_password_lemah(): void
    {
        $res = $this->postJson('/api/v1/auth/register', $this->registerPayload([
            'password' => 'password', // tanpa kapital & angka
            'password_confirmation' => 'password',
        ]));

        $res->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_register_ulang_email_yang_sama_mengganti_kandidat_dan_handle(): void
    {
        $first = $this->registerPending();

        // Cooldown OTP masih aktif → majukan waktu
        $this->travel(61)->seconds();

        $second = $this->registerPending();

        $this->assertNotSame($first->verify_handle, $second->verify_handle);
        $this->assertSame(1, PendingRegistration::where('email', 'rina@example.com')->count());
    }

    public function test_login_tidak_membuka_pintu_untuk_pendaftar_baru(): void
    {
        // Pendaftar baru belum punya baris users → kredensial tak dikenal (401).
        $this->registerPending();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'rina@example.com',
            'password' => 'Rahasia123',
        ])->assertStatus(401);
    }

    public function test_login_diblokir_untuk_akun_legacy_belum_verifikasi(): void
    {
        // Akun pra-pending (dibuat langsung, tanpa lewat register baru)
        $user = User::create([
            'name' => 'Lama',
            'email' => 'lama@example.com',
            'password' => 'Rahasia123', // cast hashed
            'is_active' => true,
        ]);
        $user->assignRole('pasien');

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'lama@example.com',
            'password' => 'Rahasia123',
        ]);

        $res->assertStatus(403)
            ->assertJsonPath('errors.code.0', 'email_unverified');
    }

    public function test_verifikasi_otp_benar_membuat_user_terverifikasi_dan_menghasilkan_token(): void
    {
        $pending = $this->registerPending();
        $otp = $this->latestPlainOtp();

        $res = $this->postJson('/api/v1/auth/email/verify', [
            'email' => 'rina@example.com',
            'code' => $otp,
            'verify_handle' => $pending->verify_handle,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonStructure(['data' => ['token']]);

        // Baru SEKARANG email masuk users — dengan status terverifikasi
        $user = User::where('email', 'rina@example.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'rina@example.com']);
    }

    public function test_verifikasi_dengan_handle_salah_ditolak(): void
    {
        $this->registerPending();
        $otp = $this->latestPlainOtp();

        $res = $this->postJson('/api/v1/auth/email/verify', [
            'email' => 'rina@example.com',
            'code' => $otp,
            'verify_handle' => str_repeat('x', 64),
        ]);

        $res->assertStatus(422);
        $this->assertNull(User::where('email', 'rina@example.com')->first());
    }

    public function test_verifikasi_otp_salah_mengurangi_attempt_dan_mengunci_setelah_batas(): void
    {
        $pending = $this->registerPending();

        // 5 tebakan salah (max_attempts default) — attempt menyusut per hit
        for ($i = 5; $i >= 1; $i--) {
            $res = $this->postJson('/api/v1/auth/email/verify', [
                'email' => 'rina@example.com',
                'code' => '000000',
                'verify_handle' => $pending->verify_handle,
            ]);
            $res->assertStatus(422);

            if ($i > 1) {
                $this->assertSame($i - 1, $res->json('errors.attempts_left'));
            }
        }

        // Melewati rate limiter (TTL cache habis) — OTP masih berlaku
        // (TTL 10 menit) tapi sudah terkunci karena attempt habis.
        $this->travel(61)->seconds();

        $otp = $this->latestPlainOtp();
        $this->postJson('/api/v1/auth/email/verify', [
            'email' => 'rina@example.com',
            'code' => $otp,
            'verify_handle' => $pending->verify_handle,
        ])->assertStatus(422);

        // Tidak ada user yang tercipta dari kode yang gagal
        $this->assertNull(User::where('email', 'rina@example.com')->first());
    }

    public function test_verifikasi_tanpa_handle_tetap_berhasil_bila_kode_benar(): void
    {
        // Sesi halaman OTP bisa kehilangan handle (tab lama, re-register dsb.)
        // — kode yang benar SUDAH bukti kepemilikan email, jadi verifikasi
        // diteruskan. Handle salah tetap ditolak (test terpisah).
        $this->registerPending();
        $otp = $this->latestPlainOtp();

        $this->postJson('/api/v1/auth/email/verify', [
            'email' => 'rina@example.com',
            'code' => $otp,
        ])->assertOk()
            ->assertJsonPath('data.verified', true);

        $this->assertNotNull(User::where('email', 'rina@example.com')->first());
        $this->assertDatabaseMissing('pending_registrations', ['email' => 'rina@example.com']);
    }

    public function test_resend_menolak_sebelum_cooldown_selesai(): void
    {
        $pending = $this->registerPending();

        $res = $this->postJson('/api/v1/auth/email/resend', [
            'email' => 'rina@example.com',
            'verify_handle' => $pending->verify_handle,
        ]);
        $res->assertStatus(429);
        $this->assertNotNull($res->json('errors.retry_after'));
    }

    public function test_resend_setelah_cooldown_mengirim_kode_baru(): void
    {
        $pending = $this->registerPending();

        // Majukan waktu melewati cooldown 60 detik
        $this->travel(61)->seconds();

        $res = $this->postJson('/api/v1/auth/email/resend', [
            'email' => 'rina@example.com',
            'verify_handle' => $pending->verify_handle,
        ]);
        $res->assertOk()->assertJsonPath('data.resent', true);

        Mail::assertQueued(VerifyEmailOtp::class, 2);
    }

    public function test_resend_untuk_email_tak_dikenal_tetap_generik_200(): void
    {
        $res = $this->postJson('/api/v1/auth/email/resend', ['email' => 'hantu@example.com']);
        $res->assertOk();
        $this->assertFalse($res->json('data.resent'));
    }

    public function test_login_berhasil_setelah_verifikasi(): void
    {
        $pending = $this->registerPending();
        $otp = $this->latestPlainOtp();

        $this->postJson('/api/v1/auth/email/verify', [
            'email' => 'rina@example.com',
            'code' => $otp,
            'verify_handle' => $pending->verify_handle,
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'rina@example.com',
            'password' => 'Rahasia123',
        ])->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_otp_disimpan_sebagai_hash_bukan_plaintext(): void
    {
        $this->registerPending();
        $otp = $this->latestPlainOtp();

        $row = EmailVerificationOtp::where('email', 'rina@example.com')->latest('id')->first();

        $this->assertNotSame($otp, $row->code_hash);
        $this->assertGreaterThan(30, strlen($row->code_hash));
    }

    /**
     * Ambil OTP "plaintext" terakhir dari mailable yang di-queue (properti
     * publik mailable menyimpan kode sebelum dikirim).
     */
    private function latestPlainOtp(): string
    {
        $queued = collect(Mail::queued(VerifyEmailOtp::class))
            ->filter(fn ($m) => strtolower($m->maskedEmail) !== '')
            ->last();

        if ($queued && property_exists($queued, 'otp')) {
            return $queued->otp;
        }

        $this->fail('Tidak menemukan OTP pada mailable terkirim.');
    }
}
