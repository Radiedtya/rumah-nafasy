<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'old_date',
        'old_start_time',
        'old_end_time',
        'new_date',
        'new_start_time',
        'new_end_time',
        'reason',
        'rescheduled_by',
    ];

    protected function casts(): array
    {
        return [
            'old_date' => 'date',
            'new_date' => 'date',
            'old_start_time' => 'datetime:H:i',
            'old_end_time' => 'datetime:H:i',
            'new_start_time' => 'datetime:H:i',
            'new_end_time' => 'datetime:H:i',
        ];
    }

    // Relationships

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Helpers

    public function isByPasien(): bool
    {
        return $this->rescheduled_by === 'pasien';
    }

    public function isByPsikolog(): bool
    {
        return $this->rescheduled_by === 'psikolog';
    }

    public function isByAdmin(): bool
    {
        return $this->rescheduled_by === 'admin';
    }
}