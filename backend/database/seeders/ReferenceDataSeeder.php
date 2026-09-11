<?php

namespace Database\Seeders;

use App\Models\Specialization;
use App\Models\ClientCategory;
use App\Models\DurationOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        // ==================== SPECIALIZATIONS ====================

        $specializations = [
            [
                'name' => 'Klinis Dewasa',
                'description' => 'Penanganan depresi, anxiety, stress, dan masalah mental dewasa.',
                'icon' => 'brain',
            ],
            [
                'name' => 'Anak & Remaja',
                'description' => 'Konseling untuk anak dan remaja: bullying, akademis, tumbuh kembang.',
                'icon' => 'child',
            ],
            [
                'name' => 'Pernikahan & Keluarga',
                'description' => 'Konseling pasangan, konflik keluarga, parenting, dan komunikasi.',
                'icon' => 'heart',
            ],
            [
                'name' => 'Kependidikan',
                'description' => 'Konseling sekolah, motivasi belajar, pemilihan jurusan & karir.',
                'icon' => 'graduation-cap',
            ],
            [
                'name' => 'Trauma & PTSD',
                'description' => 'Penanganan trauma, kekerasan, grief, dan pasca trauma.',
                'icon' => 'shield',
            ],
            [
                'name' => 'Industri & Organisasi',
                'description' => 'Konseling stress kerja, burnout, konflik workplace, dan karir.',
                'icon' => 'briefcase',
            ],
        ];

        foreach ($specializations as $spec) {
            Specialization::firstOrCreate(
                ['name' => $spec['name']],
                array_merge($spec, ['slug' => Str::slug($spec['name']), 'is_active' => true])
            );
        }

        // ==================== CLIENT CATEGORIES ====================

        $categories = [
            [
                'name' => 'Siswa',
                'description' => 'Untuk pelajar SMP dan SMA. Harga khusus dengan verifikasi kartu pelajar.',
                'base_price' => 50000,
            ],
            [
                'name' => 'Mahasiswa',
                'description' => 'Untuk mahasiswa D3/S1. Harga khusus dengan verifikasi KTM.',
                'base_price' => 75000,
            ],
            [
                'name' => 'Umum',
                'description' => 'Untuk individu dewasa umum. Tanpa verifikasi khusus.',
                'base_price' => 150000,
            ],
            [
                'name' => 'Pasangan',
                'description' => 'Konseling untuk pasangan/menikah. Untuk dua orang.',
                'base_price' => 250000,
            ],
            [
                'name' => 'Keluarga',
                'description' => 'Konseling keluarga. Untuk tiga orang atau lebih.',
                'base_price' => 300000,
            ],
        ];

        foreach ($categories as $cat) {
            ClientCategory::firstOrCreate(
                ['name' => $cat['name']],
                array_merge($cat, ['slug' => Str::slug($cat['name']), 'is_active' => true])
            );
        }

        // ==================== DURATION OPTIONS ====================

        $durations = [
            [
                'name' => '30 Menit',
                'minutes' => 30,
                'multiplier' => 0.50,
            ],
            [
                'name' => '60 Menit',
                'minutes' => 60,
                'multiplier' => 1.00,
            ],
            [
                'name' => '90 Menit',
                'minutes' => 90,
                'multiplier' => 1.50,
            ],
        ];

        foreach ($durations as $dur) {
            DurationOption::firstOrCreate(
                ['minutes' => $dur['minutes']],
                array_merge($dur, ['is_active' => true])
            );
        }
    }
}