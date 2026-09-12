<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('slots:release-locked')]
#[Description('Release booking slots that have expired lock (>10 minutes)')]
class ReleaseLockedSlots extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expired = Booking::whereNotNull('locked_until')
            ->where('locked_until', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        $count = 0;
        foreach ($expired as $booking) {
            $booking->update([
                'status' => 'cancelled',
                'locked_until' => null,
            ]);
            $count++;
        }

        $this->info("Released {$count} locked slots.");
        return 0;
    }
}
