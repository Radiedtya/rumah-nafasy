<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'pasien_id',
        'psikolog_id',
        'booking_date',
        'start_time',
        'end_time',
        'room_id',
        'status',
        'locked_until',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'locked_until' => 'datetime',
        ];
    }

    // Relationships

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function pasien()
    {
        return $this->belongsTo(User::class, 'pasien_id');
    }

    public function psikolog()
    {
        return $this->belongsTo(User::class, 'psikolog_id');
    }

    public function consultation()
    {
        return $this->hasOne(Consultation::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function rescheduleLogs()
    {
        return $this->hasMany(RescheduleLog::class);
    }

    // Helpers

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function canReschedule(): bool
    {
        $rescheduleCount = $this->rescheduleLogs()
            ->where('rescheduled_by', 'pasien')
            ->count();

        if ($rescheduleCount >= 2) {
            return false;
        }

        $consultationTime = $this->booking_date->setTimeFromTimeString($this->start_time);

        return $consultationTime->diffInHours(now()) >= 24;
    }

    public function canCancel(): bool
    {
        $consultationTime = $this->booking_date->setTimeFromTimeString($this->start_time);

        return $consultationTime->isFuture();
    }
}