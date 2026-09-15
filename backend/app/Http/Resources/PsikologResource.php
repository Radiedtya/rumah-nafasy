<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PsikologResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->psikologProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar
                ? asset('storage/' . $this->avatar)
                : null,
            'slug' => $profile?->slug,
            'bio' => $profile?->bio,
            'specialization' => $profile?->specialization?->name,
            'specialization_slug' => $profile?->specialization?->slug,
            'specialization_icon' => $profile?->specialization?->icon,
            'experience_years' => $profile?->experience_years,
            'license_no' => $profile?->license_no,
            'education' => $profile?->education,
            'workplace' => $profile?->workplace,
            'status' => $profile?->status,
            'is_available' => $profile?->is_available,
            'rating_avg' => $profile ? (float) $profile->rating_avg : 0,
            'total_reviews' => $profile?->total_reviews ?? 0,
            'total_consultations' => $profile?->total_consultations ?? 0,
            'custom_rate' => $profile?->custom_rate ? (float) $profile->custom_rate : null,
            'schedules' => $profile && $profile->relationLoaded('schedules')
                ? ScheduleResource::collection($profile->schedules)->resolve()
                : [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}