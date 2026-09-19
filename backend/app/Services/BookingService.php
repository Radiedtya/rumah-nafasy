<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Order;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;

class BookingService
{
    /**
     * Get available time slots for a psikolog on a specific date.
     */
    public function getAvailableSlots(User $psikolog, string $date, int $durationMinutes): array
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Get psikolog's schedule for this day
        $schedules = Schedule::where('psikolog_id', $psikolog->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        // Get existing bookings for this date.
        // pending_psikolog ikut memblokir slot: slot yang diajukan tidak boleh
        // ditawarkan ke pasien lain sebelum psikolog memutuskan.
        // PENTING: pakai whereDate — kolom date di SQLite tersimpan dengan
        // komponen waktu ("Y-m-d 00:00:00"), where biasa tidak pernah match
        // dan menyebabkan double-booking.
        $existingBookings = Booking::where('psikolog_id', $psikolog->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending_psikolog', 'confirmed', 'in_progress'])
            ->get();

        $slots = [];

        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);

            // Generate slots based on duration
            while ($start->copy()->addMinutes($durationMinutes)->lte($end)) {
                $slotEnd = $start->copy()->addMinutes($durationMinutes);

                // Check if slot conflicts with existing bookings
                $isAvailable = $existingBookings->every(function ($booking) use ($start, $slotEnd) {
                    $bookingStart = Carbon::parse($booking->start_time);
                    $bookingEnd = Carbon::parse($booking->end_time);
                    // No overlap if: slot ends before booking starts OR slot starts after booking ends
                    return $slotEnd->lte($bookingStart) || $start->gte($bookingEnd);
                });

                $slots[] = [
                    'start_time' => $start->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'is_available' => $isAvailable,
                ];

                $start->addMinutes($durationMinutes);
            }
        }

        // Return only available slots
        return array_values(array_filter($slots, fn ($slot) => $slot['is_available']));
    }

    /**
     * Check if a time slot conflicts with existing bookings.
     */
    public function hasConflict(
        User $psikolog,
        string $date,
        string $startTime,
        string $endTime,
        ?int $excludeBookingId = null
    ): bool {
        $query = Booking::where('psikolog_id', $psikolog->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['pending_psikolog', 'confirmed', 'in_progress'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($q2) use ($startTime, $endTime) {
                    $q2->where('start_time', '<', $endTime)
                       ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Ringkasan ketersediaan per tanggal dalam rentang + rekomendasi slot terdekat.
     * Dipakai kalender booking: tanda ✓/✕ per tanggal & daftar 5 slot terdekat.
     * Hanya 2 query (schedules + bookings dalam rentang), sisanya dihitung di memori.
     */
    public function getAvailabilitySummary(User $psikolog, string $from, string $to, int $durationMinutes): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        $todayStart = now()->startOfDay();

        // Jadwal psikolog per hari dalam seminggu (1 query)
        $schedulesByDow = Schedule::where('psikolog_id', $psikolog->id)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        // Booking aktif dalam rentang (1 query) — whereDate aman utk SQLite/MySQL
        $bookingsByDate = Booking::where('psikolog_id', $psikolog->id)
            ->whereDate('booking_date', '>=', $from)
            ->whereDate('booking_date', '<=', $to)
            ->whereIn('status', ['pending_psikolog', 'confirmed', 'in_progress'])
            ->get()
            ->groupBy(fn ($b) => Carbon::parse($b->booking_date)->format('Y-m-d'));

        $days = [];
        $next = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $dayBookings = $bookingsByDate->get($dateKey, collect());
            $daySlots = $this->buildSlotsForDate(
                $schedulesByDow->get($date->dayOfWeek, collect()),
                $dayBookings,
                $durationMinutes,
            );
            $available = array_values(array_filter($daySlots, fn ($s) => $s['is_available']));

            $days[] = [
                'date' => $dateKey,
                'available' => count($available) > 0,
                'slots' => count($available),
                'first_start' => $available[0]['start_time'] ?? null,
            ];

            // Rekomendasi terdekat: mulai dari hari ini, maksimal 5 slot
            if ($date->gte($todayStart) && count($next) < 5) {
                foreach ($available as $slot) {
                    $next[] = [
                        'date' => $dateKey,
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                    ];
                    if (count($next) >= 5) {
                        break;
                    }
                }
            }
        }

        return ['days' => $days, 'next' => $next];
    }

    /** Bangun daftar slot untuk satu tanggal dari jadwal + booking yang sudah ada. */
    private function buildSlotsForDate($schedules, $bookings, int $durationMinutes): array
    {
        if ($schedules->isEmpty()) {
            return [];
        }

        $slots = [];
        foreach ($schedules as $schedule) {
            $start = Carbon::parse($schedule->start_time);
            $end = Carbon::parse($schedule->end_time);

            while ($start->copy()->addMinutes($durationMinutes)->lte($end)) {
                $slotEnd = $start->copy()->addMinutes($durationMinutes);
                $isAvailable = $bookings->every(function ($booking) use ($start, $slotEnd) {
                    $bookingStart = Carbon::parse($booking->start_time);
                    $bookingEnd = Carbon::parse($booking->end_time);
                    return $slotEnd->lte($bookingStart) || $start->gte($bookingEnd);
                });

                $slots[] = [
                    'start_time' => $start->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'is_available' => $isAvailable,
                ];
                $start->addMinutes($durationMinutes);
            }
        }

        return $slots;
    }

    /**
     * Check if psikolog is available on that day & time.
     */
    public function isPsikologAvailable(User $psikolog, string $date, string $startTime, string $endTime): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        return Schedule::where('psikolog_id', $psikolog->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->exists();
    }

    /**
     * Calculate refund amount based on time until consultation.
     *
     * - H-3 or more: 100% refund
     * - H-2 (48-72h): 75% refund
     * - H-1 (24-48h): 50% refund
     * - H-0 (<24h): 0% refund
     */
    public function calculateRefundAmount(Order $order, Carbon $consultationTime): float
    {
        $hoursUntilConsultation = now()->diffInHours($consultationTime, false);

        if ($hoursUntilConsultation >= 72) {
            return (float) $order->calculated_price;
        } elseif ($hoursUntilConsultation >= 48) {
            return (float) $order->calculated_price * 0.75;
        } elseif ($hoursUntilConsultation >= 24) {
            return (float) $order->calculated_price * 0.50;
        } else {
            return 0;
        }
    }

    /**
     * Get refund percentage label.
     */
    public function getRefundPercentage(float $refundAmount, float $orderAmount): int
    {
        if ($orderAmount <= 0) {
            return 0;
        }

        return (int) round(($refundAmount / $orderAmount) * 100);
    }
}