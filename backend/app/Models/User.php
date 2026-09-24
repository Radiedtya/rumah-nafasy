<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'google_id',
        'is_active',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Relationships

    public function psikologProfile()
    {
        return $this->hasOne(PsikologProfile::class);
    }

    public function ordersAsPasien()
    {
        return $this->hasMany(Order::class, 'pasien_id');
    }

    public function ordersAsPsikolog()
    {
        return $this->hasMany(Order::class, 'psikolog_id');
    }

    public function bookingsAsPasien()
    {
        return $this->hasMany(Booking::class, 'pasien_id');
    }

    public function bookingsAsPsikolog()
    {
        return $this->hasMany(Booking::class, 'psikolog_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'pasien_id');
    }

    public function patientVerifications()
    {
        return $this->hasMany(PatientVerification::class, 'pasien_id');
    }

    // Helpers

    public function isPasien(): bool
    {
        return $this->hasRole('pasien');
    }

    public function isPsikolog(): bool
    {
        return $this->hasRole('psikolog');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Assign role dengan jaminan role ADA (self-healing).
     *
     * Spatie melempar RoleDoesNotExist bila tabel roles kosong — misal
     * seeder RolePermissionSeeder belum dijalankan di environment baru.
     * Tanpa ini, login Google / verifikasi OTP / pembuatan psikolog oleh
     * admin meledak 500 SETELAH user dibuat → user "yatim" tanpa role.
     * firstOrCreate membuat role yang hilang secara idempotent, lalu assign.
     */
    public function assignRoleSafe(string $role): static
    {
        \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => $role,
            'guard_name' => 'web',
        ]);

        return $this->assignRole($role);
    }
}
