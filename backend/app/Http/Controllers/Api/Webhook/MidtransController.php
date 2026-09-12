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
     * Handle Midtrans payment notification.
     * Also handles mock payment success for development.
     */
    public function handle(Request $request)
    {
        // Mock mode: simulate payment success
        if (!config('midtrans.is_configured') || $request->query('mock')) {
            $orderNumber = $request->query('order_id') ?? $request->input('order_id');

            if (!$orderNumber) {
                return $this->errorResponse('Order ID required', 400);
            }

            $order = Order::where('order_number', $orderNumber)->first();

            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }

            // Update payment
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

            // Update order status
            $order->update([
                'status' => 'paid',
                'expires_at' => now()->addDays(7), // 7 hari untuk pilih jadwal
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
            ], 'Mock payment success');
        }

        // Real Midtrans notification
        try {
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

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to process notification: ' . $e->getMessage(), 500);
        }
    }
}