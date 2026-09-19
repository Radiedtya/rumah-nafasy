<?php

namespace App\Http\Controllers\Api\Pasien;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
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
     * Simulasi pembayaran berhasil — DEVELOPMENT ONLY.
     *
     * Hanya bisa dipicu oleh PEMILIK order yang sedang login, dan hanya
     * aktif saat Midtrans TIDAK dikonfigurasi. Di produksi (keys terisi)
     * endpoint ini selalu ditolak 403. Tidak ada lagi endpoint publik
     * untuk menandai order lunas.
     */
    public function mockSuccess(Request $request, Order $order)
    {
        if (config('midtrans.is_configured')) {
            return $this->errorResponse('Endpoint simulasi tidak tersedia', 403);
        }

        if ($order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        if (!$order->isPendingPayment()) {
            return $this->errorResponse('Pesanan tidak dapat dibayar (status: ' . $order->status . ')', 422);
        }

        $payment = Payment::where('order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $payment->update([
                'status' => 'success',
                'payment_channel' => 'mock',
                'transaction_id' => 'mock-' . uniqid(),
                'paid_at' => now(),
                'payload' => array_merge($payment->payload ?? [], ['mock_notification' => true]),
            ]);
        }

        $order->update([
            'status' => 'paid',
            'expires_at' => now()->addDays(7),
        ]);

        app(FonnteService::class)->notifyUser(
            $order->pasien,
            WhatsAppMessages::paymentSuccessPasien($order)
        );
        app(FonnteService::class)->notifyUser(
            $order->psikolog,
            WhatsAppMessages::paymentSuccessPsikolog($order)
        );

        return $this->successResponse([
            'order_number' => $order->order_number,
            'status' => 'paid',
        ], 'Pembayaran simulasi berhasil');
    }
}