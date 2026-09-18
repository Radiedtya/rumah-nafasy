<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar
                ? '/storage/' . $this->avatar
                : null,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'phone_verified_at' => $this->phone_verified_at?->toISOString(),
            'is_active' => $this->is_active,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'psikolog_profile' => $this->whenLoaded('psikologProfile', function () {
                return [
                    'id' => $this->psikologProfile->id,
                    'slug' => $this->psikologProfile->slug,
                    'bio' => $this->psikologProfile->bio,
                    'specialization' => $this->psikologProfile->specialization?->name,
                    'experience_years' => $this->psikologProfile->experience_years,
                    'license_no' => $this->psikologProfile->license_no,
                    'education' => $this->psikologProfile->education,
                    'workplace' => $this->psikologProfile->workplace,
                    'status' => $this->psikologProfile->status,
                    'is_available' => $this->psikologProfile->is_available,
                    'rating_avg' => (float) $this->psikologProfile->rating_avg,
                    'total_reviews' => $this->psikologProfile->total_reviews,
                    'total_consultations' => $this->psikologProfile->total_consultations,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}