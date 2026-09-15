<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'minutes' => $this->minutes,
            'multiplier' => (float) $this->multiplier,
            'is_active' => $this->is_active,
        ];
    }
}