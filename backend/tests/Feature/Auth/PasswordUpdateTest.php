<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailOtp;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_ubah_password_tanpa_current_password_ditolak(): void
    {
        $user = $this->verifiedUser();

        $res = $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'password' => 'PasswordBaru1',
            'password_confirmation' => 'PasswordBaru1',
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_ubah_password_dengan_current_password_salah_ditolak(): void
    {
        $user = $this->verifiedUser();

        $res = $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'current_password' => 'BukanPassword1',
            'password' => 'PasswordBaru1',
            'password_confirmation' => 'PasswordBaru1',
        ]);

        $res->assertStatus(422);
    }

    public function test_ubah_password_sukses_mencabut_token_lain(): void
    {
        $user = $this->verifiedUser();

        // Token "perangkat lain"
        $otherToken = $user->createToken('other-device')->accessToken;

        $res = $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'current_password' => 'Rahasia123',
            'password' => 'PasswordBaru1',
            'password_confirmation' => 'PasswordBaru1',
        ]);

        $res->assertOk();

        // Token perangkat lain dicabut dari DB
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $otherToken->id,
        ]);

        // Password baru berlaku untuk login
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'PasswordBaru1',
        ])->assertOk();
    }

    public function test_set_password_akun_google_murni_wajib_otp(): void
    {
        $user = User::create([
            'name' => 'Google User',
            'email' => 'gugel@example.com',
            'google_id' => 'g-123',
            'password' => null,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('pasien');

        // Tanpa OTP → ditolak
        $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'password' => 'PasswordBaru1',
            'password_confirmation' => 'PasswordBaru1',
        ])->assertStatus(422)->assertJsonValidationErrors(['otp_code']);

        // Minta OTP → dapat otp_token + kode terkirim
        Mail::fake();
        $res = $this->actingAs($user)->postJson('/api/v1/auth/password/set-otp');
        $res->assertOk();

        $otpToken = $res->json('data.otp_token');
        $this->assertNotNull($otpToken);

        $otp = collect(Mail::queued(VerifyEmailOtp::class))->last()->otp;

        // Set password dengan OTP → sukses
        $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'otp_token' => $otpToken,
            'otp_code' => $otp,
            'password' => 'PasswordBaru1',
            'password_confirmation' => 'PasswordBaru1',
        ])->assertOk();

        // Login email+password kini bisa
        $this->postJson('/api/v1/auth/login', [
            'email' => 'gugel@example.com',
            'password' => 'PasswordBaru1',
        ])->assertOk();
    }

    public function test_otp_token_sekali_pakai(): void
    {
        $user = User::create([
            'name' => 'Google User',
            'email' => 'gugel2@example.com',
            'google_id' => 'g-456',
            'password' => null,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('pasien');

        Mail::fake();
        $res = $this->actingAs($user)->postJson('/api/v1/auth/password/set-otp');
        $otpToken = $res->json('data.otp_token');
        $otp = collect(Mail::queued(VerifyEmailOtp::class))->last()->otp;

        // Pakai pertama → sukses
        $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'otp_token' => $otpToken,
            'otp_code' => $otp,
            'password' => 'PasswordBaru1',
            'password_confirmation' => 'PasswordBaru1',
        ])->assertOk();

        // Token yang sama dipakai lagi → ditolak
        $user->forceFill(['password' => null])->save();
        $this->actingAs($user)->putJson('/api/v1/auth/password', [
            'otp_token' => $otpToken,
            'otp_code' => $otp,
            'password' => 'PasswordBaru2',
            'password_confirmation' => 'PasswordBaru2',
        ])->assertStatus(422);
    }

    private function verifiedUser(): User
    {
        $user = User::create([
            'name' => 'Rina',
            'email' => 'rina-pw@example.com',
            'password' => Hash::make('Rahasia123'),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('pasien');

        return $user;
    }
}
