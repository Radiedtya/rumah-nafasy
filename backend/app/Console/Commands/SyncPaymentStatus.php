<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payments:sync-status')]
#[Description('Sync pending payments and expire old ones (>24h)')]
class SyncPaymentStatus extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Find orders with expired payment deadline
        $expiredOrders = Order::where('status', 'pending_payment')
            ->where('expires_at', '<', now())
            ->get();

        $count = 0;
        foreach ($expiredOrders as $order) {
            // Update payment to failed
            $payment = Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->first();

            if ($payment) {
                $payment->update(['status' => 'failed']);
            }

            // Cancel order
            $order->update(['status' => 'cancelled']);
            $count++;
        }

        $this->info("Expired {$count} pending payments.");
        return 0;
    }
}
