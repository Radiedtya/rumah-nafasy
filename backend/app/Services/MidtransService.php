<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Str;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        if (config('midtrans.is_configured')) {
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');
            Config::$isSanitized = true;
            Config::$is3ds = true;
        }
    }

    /**
     * Create Snap transaction for an order.
     * Returns token + redirect_url.
     * If Midtrans not configured, returns mock data for development.
     */
    public function createSnapTransaction(Order $order): array
    {
        // Mode mock untuk development tanpa Midtrans keys.
        // URL mengarah ke halaman pembayaran frontend (bukan webhook!);
        // simulasi hanya bisa dipicu pemilik order via endpoint mockSuccess.
        if (!config('midtrans.is_configured')) {
            $appUrl = rtrim(config('app.frontend_url', config('app.url', 'http://localhost:3000')), '/');
            return [
                'token' => 'mock-' . Str::random(16),
                'redirect_url' => $appUrl . '/dashboard/booking/payment/' . $order->order_number,
                'is_mock' => true,
            ];
        }

        // Real Midtrans Snap
        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) round($order->calculated_price),
            ],
            'customer_details' => [
                'first_name' => $order->pasien->name,
                'email' => $order->pasien->email,
                'phone' => $order->pasien->phone,
            ],
            'item_details' => [
                [
                    'id' => 'KNS-' . $order->psikolog_id,
                    'name' => 'Konsultasi ' . $order->psikolog->name,
                    'price' => (int) round($order->calculated_price),
                    'quantity' => 1,
                    'category' => 'Konsultasi Psikologi',
                ],
            ],
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => 24,
            ],
        ];

        $snap = Snap::createTransaction($params);

        return [
            'token' => $snap->token,
            'redirect_url' => $snap->redirect_url,
            'is_mock' => false,
        ];
    }

    /**
     * Handle Midtrans notification webhook.
     * Returns parsed notification data.
     */
    public function handleNotification(array $payload): array
    {
        // Mock mode
        if (!config('midtrans.is_configured')) {
            return [
                'order_id' => $payload['order_id'] ?? null,
                'transaction_status' => 'settlement',
                'payment_type' => 'mock',
                'transaction_id' => 'mock-' . Str::random(10),
                'gross_amount' => $payload['gross_amount'] ?? 0,
                'is_mock' => true,
            ];
        }

        // Real Midtrans notification
        $notification = new \Midtrans\Notification();

        return [
            'order_id' => $notification->order_id,
            'transaction_status' => $notification->transaction_status,
            'payment_type' => $notification->payment_type,
            'transaction_id' => $notification->transaction_id,
            'gross_amount' => $notification->gross_amount,
            'fraud_status' => $notification->fraud_status ?? null,
            'is_mock' => false,
        ];
    }

    /**
     * Map Midtrans transaction status to our PaymentStatus.
     */
    public function mapStatus(string $midtransStatus): string
    {
        return match ($midtransStatus) {
            'settlement', 'capture' => 'success',
            'pending' => 'pending',
            'deny', 'cancel', 'expire', 'failure' => 'failed',
            'refund' => 'refunded',
            default => 'pending',
        };
    }
}