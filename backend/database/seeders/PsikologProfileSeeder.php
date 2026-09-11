<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Specialization;
use App\Models\PsikologProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PsikologProfileSeeder extends Seeder
{
    public function run(): void
    {
        $psikologs = User::role('psikolog')->get();
        $specializations = Specialization::all();

        $profileData = [
            0 => [
                'bio' => 'Psikolog klinis dengan pengalaman 10 tahun dalam penanganan depresi, anxiety, dan stress management. Pendekatan terapi CBT dan terapi dukungan.',
                'experience_years' => 10,
                'education' => 'S2 Psikologi Klinis, Universitas Indonesia',
                'workplace' => 'Praktik Pribadi, Jakarta',
                'specialization' => 'Klinis Dewasa',
            ],
            1 => [
                'bio' => 'Spesialis psikologi anak dan remaja. Berpengalaman menangani bullying, masalah akademis, dan tumbuh kembang. Pendekatan play therapy dan konseling.',
                'experience_years' => 8,
                'education' => 'S2 Psikologi Anak, Universitas Gadjah Mada',
                'workplace' => 'Klinik Mental Sehat Anak, Jakarta',
                'specialization' => 'Anak & Remaja',
            ],
            2 => [
                'bio' => 'Konselor pernikahan dan keluarga. Membantu pasangan mengatasi konflik, masalah komunikasi, dan parenting. Pendekatan terapi sistemik keluarga.',
                'experience_years' => 12,
                'education' => 'S2 Psikologi Klinis, Universitas Padjadjaran',
                'workplace' => 'Praktik Bersama, Bandung',
                'specialization' => 'Pernikahan & Keluarga',
            ],
            3 => [
                'bio' => 'Psikolog kependidikan dengan fokus pada konseling karir dan motivasi belajar. Sering menjadi narasumber di sekolah-sekolah.',
                'experience_years' => 7,
                'education' => 'S1 Psikologi, Institut Teknologi Bandung',
                'workplace' => 'Yayasan Pendidikan Sehat, Surabaya',
                'specialization' => 'Kependidikan',
            ],
            4 => [
                'bio' => 'Spesialis trauma dan PTSD. Berpengalaman menangani korban kekerasan, kecelakaan, dan grief. Terapi EMDR dan trauma-focused CBT.',
                'experience_years' => 15,
                'education' => 'S3 Psikologi Klinis, Universitas Airlangga',
                'workplace' => 'RS Premier, Surabaya',
                'specialization' => 'Trauma & PTSD',
            ],
        ];

        foreach ($psikologs as $index => $psikolog) {
            $data = $profileData[$index] ?? $profileData[0];

            $specialization = $specializations->firstWhere('name', $data['specialization']);

            PsikologProfile::firstOrCreate(
                ['user_id' => $psikolog->id],
                [
                    'specialization_id' => $specialization?->id,
                    'slug' => Str::slug($psikolog->name . '-' . uniqid()),
                    'bio' => $data['bio'],
                    'experience_years' => $data['experience_years'],
                    'license_no' => 'SIP-' . (10000 + $index),
                    'education' => $data['education'],
                    'workplace' => $data['workplace'],
                    'status' => 'verified',
                    'verified_at' => now(),
                    'is_available' => true,
                    'rating_avg' => 4.5 + ($index * 0.1),
                    'total_reviews' => 20 + ($index * 15),
                    'total_consultations' => 100 + ($index * 50),
                ]
            );
        }
    }
}