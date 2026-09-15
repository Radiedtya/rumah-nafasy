<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking' => $this->whenLoaded('booking', function () {
                return [
                    'id' => $this->booking->id,
                    'booking_date' => $this->booking->booking_date?->format('Y-m-d'),
                    'start_time' => $this->booking->start_time,
                    'end_time' => $this->booking->end_time,
                    'room_id' => $this->booking->room_id,
                    'status' => $this->booking->status,
                    'pasien' => [
                        'id' => $this->booking->pasien?->id,
                        'name' => $this->booking->pasien?->name,
                    ],
                    'order' => [
                        'order_number' => $this->booking->order?->order_number,
                        'category_name' => $this->booking->order?->category?->name,
                        'duration_name' => $this->booking->order?->duration?->name,
                        'consultation_type' => $this->booking->order?->consultation_type,
                    ],
                ];
            }),
            'status' => $this->status,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'notes' => $this->whenLoaded('notes', function () {
                return ConsultationNoteResource::collection($this->notes);
            }),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}