<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'pasien' => [
                'id' => $this->pasien?->id,
                'name' => $this->pasien?->name,
            ],
            'psikolog' => [
                'id' => $this->psikolog?->id,
                'name' => $this->psikolog?->name,
                'slug' => $this->psikolog?->psikologProfile?->slug,
                'specialization' => $this->psikolog?->psikologProfile?->specialization?->name,
                'avatar' => $this->psikolog?->avatar
                    ? asset('storage/' . $this->psikolog->avatar)
                    : null,
            ],
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'base_price' => (float) $this->category->base_price,
                ];
            }),
            'duration' => $this->whenLoaded('duration', function () {
                return [
                    'id' => $this->duration->id,
                    'name' => $this->duration->name,
                    'minutes' => $this->duration->minutes,
                    'multiplier' => (float) $this->duration->multiplier,
                ];
            }),
            'calculated_price' => (float) $this->calculated_price,
            'consultation_type' => $this->consultation_type,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'payment' => $this->whenLoaded('payment', function () {
                return new PaymentResource($this->payment);
            }),
            'booking' => $this->whenLoaded('booking', function () {
                return [
                    'id' => $this->booking->id,
                    'booking_date' => $this->booking->booking_date?->format('Y-m-d'),
                    'start_time' => $this->booking->start_time,
                    'end_time' => $this->booking->end_time,
                    'status' => $this->booking->status,
                    'room_id' => $this->booking->room_id,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}