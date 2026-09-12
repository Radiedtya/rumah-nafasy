<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('orders:no-schedule-reminder')]
#[Description('Remind patients who paid but not picked schedule (Day 3 & Day 6)')]
class RemindNoSchedule extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FonnteService $fonnte): int
    {
        $paidOrders = Order::where('status', 'paid')
            ->whereDoesntHave('booking')
            ->with(['pasien', 'psikolog'])
            ->get();

        $count = 0;
        foreach ($paidOrders as $order) {
            $daysSincePayment = $order->updated_at->diffInDays(now());

            // Remind on Day 3 and Day 6
            if (in_array($daysSincePayment, [3, 6])) {
                $daysLeft = 7 - $daysSincePayment;
                $fonnte->notifyUser(
                    $order->pasien,
                    WhatsAppMessages::noScheduleReminder($order, $daysLeft)
                );
                $count++;
            }
        }

        $this->info("Sent no-schedule reminders for {$count} orders.");
        return 0;
    }
}
