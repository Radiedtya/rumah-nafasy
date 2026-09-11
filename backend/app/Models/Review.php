<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'pasien_id',
        'psikolog_id',
        'rating',
        'comment',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    // Relationships

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function pasien()
    {
        return $this->belongsTo(User::class, 'pasien_id');
    }

    public function psikolog()
    {
        return $this->belongsTo(User::class, 'psikolog_id');
    }

    // Helpers

    public function isPublished(): bool
    {
        return $this->is_published;
    }

    // Boot — update psikolog rating saat review dibuat

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            $review->updatePsikologRating();
        });

        static::updated(function ($review) {
            $review->updatePsikologRating();
        });

        static::deleted(function ($review) {
            $review->updatePsikologRating();
        });
    }

    public function updatePsikologRating(): void
    {
        $profile = $this->psikolog->psikologProfile;

        if ($profile) {
            $reviews = Review::where('psikolog_id', $this->psikolog_id)
                ->where('is_published', true)
                ->get();

            $profile->update([
                'rating_avg' => $reviews->avg('rating') ?? 0,
                'total_reviews' => $reviews->count(),
            ]);
        }
    }
}