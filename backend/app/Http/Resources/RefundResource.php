<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'calculated_price' => (float) $this->order->calculated_price,
                    'pasien_name' => $this->order->pasien?->name,
                    'psikolog_name' => $this->order->psikolog?->name,
                ];
            }),
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'processed_by' => $this->whenLoaded('processedBy', function () {
                return ['id' => $this->processedBy->id, 'name' => $this->processedBy->name];
            }),
            'processed_at' => $this->processed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}