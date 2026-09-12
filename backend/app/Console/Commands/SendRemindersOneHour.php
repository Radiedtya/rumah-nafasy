<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('reminders:one-hour')]
#[Description('Send WhatsApp reminders for consultations starting in ~1 hour')]
class SendRemindersOneHour extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(FonnteService $fonnte): int
    {
        $from = now()->addMinutes(50)->format('H:i:s');
        $to = now()->addMinutes(70)->format('H:i:s');

        $bookings = Booking::where('booking_date', now()->format('Y-m-d'))
            ->where('status', 'confirmed')
            ->whereTime('start_time', '>=', $from)
            ->whereTime('start_time', '<=', $to)
            ->with(['pasien', 'psikolog', 'order'])
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings starting in ~1 hour.');
            return 0;
        }

        $count = 0;
        foreach ($bookings as $booking) {
            $fonnte->notifyUser(
                $booking->pasien,
                WhatsAppMessages::reminder1Hour($booking)
            );

            $fonnte->notifyUser(
                $booking->psikolog,
                WhatsAppMessages::reminder1HourPsikolog($booking)
            );

            $count++;
        }

        $this->info("Sent 1-hour reminders for {$count} bookings.");
        return 0;
    }
}
