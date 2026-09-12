<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Refund;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:auto-expire')]
#[Description('Auto-expire paid orders not scheduled within 7 days + auto-refund')]
class AutoExpireOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FonnteService $fonnte): int
    {
        $expiredOrders = Order::where('status', 'paid')
            ->whereDoesntHave('booking')
            ->where('updated_at', '<', now()->subDays(7))
            ->with(['pasien', 'psikolog'])
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('No orders to expire.');
            return 0;
        }

        $count = 0;
        foreach ($expiredOrders as $order) {
            // Create auto-refund (100%)
            Refund::create([
                'order_id' => $order->id,
                'amount' => $order->calculated_price,
                'reason' => 'Auto-expire: tidak memilih jadwal dalam 7 hari',
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            // Update order
            $order->update(['status' => 'expired']);

            // WA notification
            $fonnte->notifyUser(
                $order->pasien,
                WhatsAppMessages::orderExpired($order)
            );

            $count++;
        }

        $this->info("Auto-expired {$count} orders with 100% refund.");
        return 0;
    }
}
