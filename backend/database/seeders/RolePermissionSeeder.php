<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==================== PERMISSIONS ====================

        // Pasien permissions
        $pasienPermissions = [
            'view psikolog',
            'view psikolog detail',
            'create order',
            'view own orders',
            'create booking',
            'reschedule own booking',
            'cancel own booking',
            'create review',
            'view own consultations',
            'update own profile',
        ];

        // Psikolog permissions
        $psikologPermissions = [
            'view own bookings',
            'update booking status',
            'reschedule own booking',
            'view own consultations',
            'create consultation notes',
            'view own income',
            'manage own schedule',
            'update own profile',
        ];

        // Admin permissions
        $adminPermissions = [
            'manage psikolog',
            'verify psikolog',
            'suspend psikolog',
            'manage categories',
            'manage durations',
            'manage specializations',
            'view all transactions',
            'manage refunds',
            'view reports',
            'view dashboard',
        ];

        $allPermissions = array_unique(array_merge(
            $pasienPermissions,
            $psikologPermissions,
            $adminPermissions
        ));

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ==================== ROLES ====================

        $pasienRole = Role::firstOrCreate(['name' => 'pasien']);
        $psikologRole = Role::firstOrCreate(['name' => 'psikolog']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);

        // Assign permissions to roles
        $pasienRole->syncPermissions($pasienPermissions);
        $psikologRole->syncPermissions($psikologPermissions);
        $adminRole->syncPermissions($adminPermissions);
    }
}