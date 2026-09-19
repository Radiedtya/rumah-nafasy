<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'google_id',
        'is_active',
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
}