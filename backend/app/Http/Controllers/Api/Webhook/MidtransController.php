<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Api\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function __construct(
        private MidtransService $midtransService
    ) {}

    /**
     * Handle Midtrans payment notification (server-to-server).
     *
     * Keamanan:
     * - Endpoint ini TIDAK punya jalur mock — simulasi pembayaran dilakukan
     *   lewat POST /pasien/orders/{order}/mock-success (auth + pemilik order,
     *   hanya aktif saat Midtrans belum dikonfigurasi).
     * - Fail closed: jika Midtrans belum dikonfigurasi, notifikasi ditolak
     *   (503) — jangan pernah memproses payload tanpa verifikasi.
     * - Signature diverifikasi MidtransService.handleNotification();
     *   payload tanpa signature valid ditolak.
     */
    public function handle(Request $request)
    {
        if (!config('midtrans.is_configured')) {
            return $this->errorResponse('Payment gateway tidak aktif', 503);
        }

        try {
            // Verifikasi signature_key Midtrans; lempar exception jika tidak valid
            $notification = $this->midtransService->handleNotification($request->all());

            $order = Order::where('order_number', $notification['order_id'])->first();

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            $payment = Payment::where('order_id', $order->id)->first();

            if (!$payment) {
                return $this->errorResponse('Payment not found', 404);
            }

            $mappedStatus = $this->midtransService->mapStatus($notification['transaction_status']);

            $payment->update([
                'status' => $mappedStatus,
                'payment_channel' => $notification['payment_type'],
                'transaction_id' => $notification['transaction_id'],
                'paid_at' => $mappedStatus === 'success' ? now() : null,
                'payload' => $notification,
            ]);

            // Update order based on payment status
            if ($mappedStatus === 'success') {
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
            } elseif ($mappedStatus === 'failed') {
                $order->update(['status' => 'cancelled']);
            }

            return $this->successResponse(null, 'Notification processed');
        } catch (\Midtrans\ApiException $e) {
            // Signature tidak valid / payload menyerupai notifikasi palsu
            return $this->errorResponse('Signature tidak valid', 403);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to process notification: ' . $e->getMessage(), 500);
        }
    }
}
