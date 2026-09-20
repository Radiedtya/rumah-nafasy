<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailVerificationOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'email',
        'code_hash',
        'expires_at',
        'last_sent_at',
        'attempts_left',
        'consumed_at',
        'invalidated_reason',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts_left' => 'integer',
        ];
    }

    /**
     * Pemilik OTP — polimorfik:
     * - App\Models\PendingRegistration (pendaftar baru, belum jadi user)
     * - App\Models\User (akun legacy unverified & alur set-password)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    // Helpers

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isConsumed()
            && $this->invalidated_reason === null
            && ! $this->isExpired()
            && $this->attempts_left > 0;
    }
}
