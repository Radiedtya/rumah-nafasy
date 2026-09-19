<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PendingRegistration extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'verify_handle',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Promosikan pendaftaran yang SUDAH terverifikasi OTP menjadi user
     * sungguhan (terverifikasi otomatis), lalu hapus baris pending.
     * Atomik: user terbuat DAN kandidat terhapus bersamaan.
     */
    public function promoteToUser(): User
    {
        return DB::transaction(function () {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                'password' => $this->password, // sudah bcrypt — cast 'hashed' tidak me-rehash
                'email_verified_at' => now(),  // OTP = bukti kepemilikan email
                'phone_verified_at' => null,
                'is_active' => true,
            ]);

            $user->assignRole('pasien');

            $this->delete();

            return $user;
        });
    }
}
