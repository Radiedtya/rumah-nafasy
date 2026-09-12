<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

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
            'locked_until' => 'datetime',
        ];
    }

    // Simplified accessors — pake substr, gak pake Carbon::parse
    public function getStartTimeAttribute($value): string
    {
        return $value ? substr($value, 0, 5) : '';
    }

    public function getEndTimeAttribute($value): string
    {
        return $value ? substr($value, 0, 5) : '';
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

    // Helpers — FIX: pake booking_date->format() + accessor, bukan getRawOriginal

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

        if (!$this->booking_date) {
            return false;
        }

        // Use casted date + accessor time (clean, no double time issue)
        $dateStr = $this->booking_date->format('Y-m-d');
        $timeStr = $this->start_time; // accessor returns "H:i"

        if (!$timeStr) {
            return false;
        }

        $consultationTime = Carbon::parse($dateStr . ' ' . $timeStr);

        // Must be at least 24 hours in the future
        return $consultationTime->gt(now()->addHours(24));
    }

    public function canCancel(): bool
    {
        if (!$this->booking_date) {
            return false;
        }

        $dateStr = $this->booking_date->format('Y-m-d');
        $timeStr = $this->start_time;

        if (!$timeStr) {
            return false;
        }

        $consultationTime = Carbon::parse($dateStr . ' ' . $timeStr);

        return $consultationTime->isFuture();
    }
}