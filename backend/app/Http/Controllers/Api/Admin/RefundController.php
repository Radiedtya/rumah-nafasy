<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        $query = Refund::with(['order.pasien', 'order.psikolog.psikologProfile', 'processedBy']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $refunds = $query->latest()->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse($refunds, 'Daftar refund', RefundResource::class);
    }

    public function approve(Request $request, Refund $refund)
    {
        if (!$refund->isPending()) {
            return $this->errorResponse('Refund tidak dapat diproses (status: ' . $refund->status . ')', 422);
        }

        // Update refund
        $refund->update([
            'status' => 'completed',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        // Update order status
        $refund->order->update(['status' => 'refunded']);

        // TODO: Process actual refund via Midtrans refund API
        // TODO: Send WA notification
        app(FonnteService::class)->notifyUser(
            $refund->order->pasien,
            WhatsAppMessages::refundApproved($refund)
        );

        return $this->successResponse(
            new RefundResource($refund->fresh()->load(['order.pasien', 'order.psikolog', 'processedBy'])),
            'Refund disetujui dan diproses'
        );
    }

    public function reject(Request $request, Refund $refund)
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if (!$refund->isPending()) {
            return $this->errorResponse('Refund tidak dapat diproses', 422);
        }

        $refund->update([
            'status' => 'rejected',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
            'reason' => $refund->reason . ' | Reject reason: ' . $request->reason,
        ]);

        return $this->successResponse(
            new RefundResource($refund->fresh()->load(['order.pasien', 'order.psikolog', 'processedBy'])),
            'Refund ditolak'
        );
    }
}