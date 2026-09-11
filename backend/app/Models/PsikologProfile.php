<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PsikologProfile extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'user_id',
        'specialization_id',
        'slug',
        'bio',
        'experience_years',
        'license_no',
        'education',
        'workplace',
        'custom_rate',
        'status',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'is_available',
        'rating_avg',
        'total_reviews',
        'total_consultations',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'custom_rate' => 'decimal:2',
            'verified_at' => 'datetime',
            'is_available' => 'boolean',
            'rating_avg' => 'decimal:2',
            'total_reviews' => 'integer',
            'total_consultations' => 'integer',
        ];
    }

    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'psikolog_id', 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'psikolog_id', 'user_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'psikolog_id', 'user_id');
    }

    // Boot

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->user->name . '-' . uniqid());
            }
        });
    }

    // Helpers

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function getEffectiveRate(ClientCategory $category, DurationOption $duration): ?float
    {
        if ($this->custom_rate) {
            return (float) ($this->custom_rate * $duration->multiplier);
        }

        return $category->calculatePrice($duration);
    }
}