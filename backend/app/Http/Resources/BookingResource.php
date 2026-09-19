<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // order nullable — alur baru booking langsung tanpa order/pembayaran
            'order' => $this->whenLoaded('order', function () {
                return $this->order ? [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'calculated_price' => (float) $this->order->calculated_price,
                    'consultation_type' => $this->order->consultation_type,
                    'category_name' => $this->order->category?->name,
                    'duration_name' => $this->order->duration?->name,
                    'duration_minutes' => $this->order->duration?->minutes,
                    'status' => $this->order->status,
                ] : null;
            }),
            'pasien' => [
                'id' => $this->pasien?->id,
                'name' => $this->pasien?->name,
                'avatar' => $this->pasien?->avatar
                    ? '/storage/' . $this->pasien->avatar
                    : null,
            ],
            'psikolog' => [
                'id' => $this->psikolog?->id,
                'name' => $this->psikolog?->name,
                'avatar' => $this->psikolog?->avatar
                    ? '/storage/' . $this->psikolog->avatar
                    : null,
                'specialization' => $this->psikolog?->psikologProfile?->specialization?->name,
            ],
            // Field alur baru (booking langsung)
            'consultation_type' => $this->consultation_type,
            'duration_minutes' => $this->duration_minutes,
            'requested_category' => $this->whenLoaded('requestedCategory', function () {
                return $this->requestedCategory ? [
                    'id' => $this->requestedCategory->id,
                    'name' => $this->requestedCategory->name,
                ] : null;
            }),
            'rejected_reason' => $this->rejected_reason,
            'booking_date' => $this->booking_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'room_id' => $this->room_id,
            'status' => $this->status,
            'locked_until' => $this->locked_until?->toISOString(),
            'consultation' => $this->whenLoaded('consultation', function () {
                return $this->consultation ? [
                    'id' => $this->consultation->id,
                    'status' => $this->consultation->status,
                    'started_at' => $this->consultation->started_at?->toISOString(),
                    'ended_at' => $this->consultation->ended_at?->toISOString(),
                ] : null;
            }),
            'reschedule_count' => $this->whenLoaded('rescheduleLogs', function () {
                return $this->rescheduleLogs->where('rescheduled_by', 'pasien')->count();
            }),
            'can_reschedule' => $this->canReschedule(),
            'can_cancel' => $this->canCancel(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
