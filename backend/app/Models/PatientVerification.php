<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientVerification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pasien_id',
        'id_type',
        'id_number',
        'id_file_path',
        'status',
        'verified_at',
        'verified_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    // Relationships

    public function pasien()
    {
        return $this->belongsTo(User::class, 'pasien_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Helpers

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}