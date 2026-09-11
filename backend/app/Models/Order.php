<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'pasien_id',
        'psikolog_id',
        'category_id',
        'duration_id',
        'calculated_price',
        'consultation_type',
        'status',
        'expires_at',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'calculated_price' => 'decimal:2',
            'expires_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    // Relationships

    public function pasien()
    {
        return $this->belongsTo(User::class, 'pasien_id');
    }

    public function psikolog()
    {
        return $this->belongsTo(User::class, 'psikolog_id');
    }

    public function category()
    {
        return $this->belongsTo(ClientCategory::class, 'category_id');
    }

    public function duration()
    {
        return $this->belongsTo(DurationOption::class, 'duration_id');
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    public function refund()
    {
        return $this->hasOne(Refund::class);
    }

    // Boot

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->order_number)) {
                $model->order_number = self::generateOrderNumber();
            }

            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(1); // 24 jam untuk bayar
            }
        });
    }

    // Helpers

    public static function generateOrderNumber(): string
    {
        $prefix = 'RMF';
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$date}-{$random}";
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    public function canSchedule(): bool
    {
        return $this->isPaid() && is_null($this->scheduled_at);
    }

    public function scheduleDeadlineExpired(): bool
    {
        if (!$this->isPaid() || $this->scheduled_at) {
            return false;
        }

        return $this->updated_at->addDays(7)->isPast();
    }
}