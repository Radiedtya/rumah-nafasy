<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reminders:tomorrow')]
#[Description('Send WhatsApp reminders for consultations scheduled tomorrow (H-1)')]
class SendRemindersTomorrow extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FonnteService $fonnte): int
    {
        $tomorrow = now()->addDay()->format('Y-m-d');

        $bookings = Booking::where('booking_date', $tomorrow)
            ->where('status', 'confirmed')
            ->with(['pasien', 'psikolog', 'order.category', 'order.duration'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings for tomorrow.');
            return 0;
        }

        $count = 0;
        foreach ($bookings as $booking) {
            // To pasien
            $fonnte->notifyUser(
                $booking->pasien,
                WhatsAppMessages::reminderH1($booking)
            );

            // To psikolog
            $fonnte->notifyUser(
                $booking->psikolog,
                WhatsAppMessages::reminderH1Psikolog($booking)
            );

            $count++;
        }

        $this->info("Sent H-1 reminders for {$count} bookings (pasien + psikolog).");
        return 0;
    }
}
