<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content, // auto-decrypted by Laravel encrypted cast
            'psikolog_name' => $this->psikolog?->name,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}