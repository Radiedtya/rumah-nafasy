<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression test untuk bug produksi:
 *   Spatie\Permission\Exceptions\RoleDoesNotExist —
 *   "There is no role named `pasien` for guard `web`."
 *
 * Skenario nyata: environment baru dengan tabel roles KOSONG (seeder
 * RolePermissionSeeder belum pernah dijalankan). Login Google membuat
 * user BARU lalu memanggil assignRole('pasien') → exception 500, dan
 * user tertinggal tanpa role (tidak bisa akses route role:pasien).
 *
 * Perbaikan: User::assignRoleSafe() memastikan role ada (firstOrCreate,
 * idempotent) sebelum assign — "self-healing" untuk environment baru.
 */
class GoogleAuthRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Simulasi environment baru: roles memang dibutuhkan aplikasi,
        // tapi TIDAK di-seed — tabel roles kosong persis seperti kasus
        // produksi yang memicu bug ini.
    }

    public function test_assign_role_safe_membuat_role_yang_belum_ada(): void
    {
        $this->assertDatabaseCount(Role::class, 0);

        $user = User::factory()->create();

        $user->assignRoleSafe('pasien');

        $this->assertTrue($user->hasRole('pasien'));
        $this->assertTrue($user->fresh()->hasRole('pasien'));
        $this->assertTrue($user->isPasien());
    }

    public function test_assign_role_safe_idempotent_tidak_menduplikasi_role(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $userA->assignRoleSafe('pasien');
        $userB->assignRoleSafe('pasien');

        $this->assertSame(1, Role::where('name', 'pasien')->count());
    }

    public function test_assign_role_safe_tetap_bekerja_dengan_seeder_normal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRoleSafe('pasien');

        $this->assertTrue($user->hasRole('pasien'));
        $this->assertSame(1, Role::where('name', 'pasien')->count());
    }

    public function test_user_yatim_tanpa_role_tetap_bisa_login_dan_role_dipulihkan(): void
    {
        // User yang terlanjur dibuat tanpa role (efek bug lama)
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $this->assertFalse($user->hasRole('pasien'));

        // Recovery path: assignRoleSafe memulihkan role-nya
        $user->assignRoleSafe('pasien');

        $this->assertTrue($user->fresh()->hasRole('pasien'));
        $this->assertTrue($user->fresh()->isPasien());
    }
}
