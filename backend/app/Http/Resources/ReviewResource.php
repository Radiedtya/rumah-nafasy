<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pasien_name' => $this->pasien?->name,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}