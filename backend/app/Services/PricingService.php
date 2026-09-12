<?php

namespace App\Services;

use App\Models\ClientCategory;
use App\Models\DurationOption;
use App\Models\PsikologProfile;

class PricingService
{
    /**
     * Calculate price based on category, duration, and psikolog custom rate.
     */
    public function calculatePrice(
        ClientCategory $category,
        DurationOption $duration,
        ?PsikologProfile $profile = null
    ): float {
        // Jika psikolog punya custom_rate, gunakan itu
        // Jika tidak, gunakan base_price dari category
        $basePrice = ($profile && $profile->custom_rate)
            ? (float) $profile->custom_rate
            : (float) $category->base_price;

        return $basePrice * (float) $duration->multiplier;
    }

    /**
     * Get starting price for a psikolog (lowest possible price).
     */
    public function getStartingPrice(?PsikologProfile $profile = null): float
    {
        if ($profile && $profile->custom_rate) {
            return (float) $profile->custom_rate * 0.5; // 30 min
        }

        return (float) ClientCategory::where('is_active', true)
            ->min('base_price');
    }
}