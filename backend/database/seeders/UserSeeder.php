<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== OWNER / SUPER ADMIN ====================

        $admin = User::firstOrCreate(
            ['email' => 'nairha@rumahnatasy.id'],
            [
                'name' => 'Nairha',
                'password' => Hash::make('nairha@rumahnatasy2024!'),
                'phone' => '+6281234567890',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);

        // Hapus admin lama jika ada (migrasi dari dummy)
        User::where('email', 'admin@rumahnatasy.id')
            ->whereDoesntHave('roles', fn($q) => $q->where('name', '!=', 'admin'))
            ->where('name', 'Admin Rumah Natasy')
            ->delete();

        // ==================== PSIKOLOG ====================

        $psikologData = [
            [
                'name' => 'dr. Andi Pratama, M.Psi',
                'email' => 'andi@rumahnatasy.id',
                'phone' => '081234567891',
            ],
            [
                'name' => 'dr. Sari Dewi, M.Psi',
                'email' => 'sari@rumahnatasy.id',
                'phone' => '081234567892',
            ],
            [
                'name' => 'dr. Budi Santoso, M.Psi',
                'email' => 'budi@rumahnatasy.id',
                'phone' => '081234567893',
            ],
            [
                'name' => 'dr. Maya Sari, M.Psi',
                'email' => 'maya@rumahnatasy.id',
                'phone' => '081234567894',
            ],
            [
                'name' => 'dr. Doni Hartanto, M.Psi',
                'email' => 'doni@rumahnatasy.id',
                'phone' => '081234567895',
            ],
        ];

        foreach ($psikologData as $data) {
            $psikolog = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'phone' => $data['phone'],
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $psikolog->syncRoles(['psikolog']);
        }

        // ==================== PASIEN ====================

        $pasienData = [
            ['name' => 'Rina Wijaya', 'email' => 'rina@example.com', 'phone' => '081234567896'],
            ['name' => 'Fajar Nugroho', 'email' => 'fajar@example.com', 'phone' => '081234567897'],
            ['name' => 'Dewi Lestari', 'email' => 'dewi@example.com', 'phone' => '081234567898'],
            ['name' => 'Rizki Ramadhan', 'email' => 'rizki@example.com', 'phone' => '081234567899'],
            ['name' => 'Putri Anggraini', 'email' => 'putri@example.com', 'phone' => '081234567800'],
        ];

        foreach ($pasienData as $data) {
            $pasien = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'phone' => $data['phone'],
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $pasien->syncRoles(['pasien']);
        }
    }
}