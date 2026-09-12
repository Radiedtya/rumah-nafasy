<?php

namespace App\Http\Controllers\Api\Pasien;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private MidtransService $midtransService
    ) {}

    /**
     * Create payment for an order.
     * Returns Midtrans Snap URL.
     */
    public function create(Request $request, Order $order)
    {
        // Authorization
        if ($order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        // Check if order is pending payment
        if (!$order->isPendingPayment()) {
            return $this->errorResponse('Pesanan tidak dapat dibayar (status: ' . $order->status . ')', 422);
        }

        // Check if already has pending payment
        $existingPayment = Payment::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPayment && $existingPayment->payment_url) {
            return $this->successResponse([
                'payment' => new PaymentResource($existingPayment),
                'snap_url' => $existingPayment->payment_url,
            ], 'Pembayaran sudah dibuat sebelumnya');
        }

        // Create Midtrans Snap transaction
        $snap = $this->midtransService->createSnapTransaction($order);

        // Create payment record
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->calculated_price,
            'status' => 'pending',
            'transaction_id' => $snap['is_mock'] ? null : $snap['token'],
            'payment_url' => $snap['redirect_url'],
            'payload' => $snap,
        ]);

        return $this->successResponse([
            'payment' => new PaymentResource($payment),
            'snap_url' => $snap['redirect_url'],
            'is_mock' => $snap['is_mock'],
        ], 'Pembayaran dibuat. Silakan lanjutkan ke halaman pembayaran.');
    }

    /**
     * Check payment status.
     */
    public function show(Request $request, Payment $payment)
    {
        // Authorization via order
        if ($payment->order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        return $this->successResponse(
            new PaymentResource($payment),
            'Status pembayaran'
        );
    }

    /**
     * Mock payment success (for development only — no Midtrans keys).
     */
    public function mockSuccess(Request $request, Order $order)
    {
        if (!config('midtrans.is_configured')) {
            return $this->errorResponse('Fitur ini hanya untuk development tanpa Midtrans keys', 403);
        }

        if ($order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        return $this->errorResponse('Mock endpoint hanya tersedia saat Midtrans tidak dikonfigurasi', 403);
    }
}