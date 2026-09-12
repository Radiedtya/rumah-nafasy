<?php

namespace App\Http\Controllers\Api\Pasien;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Pasien\CreateBookingRequest;
use App\Http\Requests\Pasien\RescheduleBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Refund;
use App\Models\RescheduleLog;
// use App\Models\Schedule;
use App\Models\User;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * Get available slots for a psikolog on a specific date.
     */
    public function availableSlots(Request $request, $psikologId)
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'duration' => ['nullable', 'integer', 'in:30,60,90'],
        ]);

        $psikolog = User::role('psikolog')->with('psikologProfile')->findOrFail($psikologId);

        $duration = (int) $request->get('duration', 60);

        $slots = $this->bookingService->getAvailableSlots(
            $psikolog,
            $request->date,
            $duration
        );

        return $this->successResponse([
            'psikolog' => [
                'id' => $psikolog->id,
                'name' => $psikolog->name,
                'slug' => $psikolog->psikologProfile?->slug,
            ],
            'date' => $request->date,
            'duration_minutes' => $duration,
            'available_slots' => $slots,
            'total_slots' => count($slots),
        ], 'Available slots');
    }

    /**
     * Create booking (pick schedule after payment).
     */
    public function store(CreateBookingRequest $request, Order $order)
    {
        // Authorization
        if ($order->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        // Check if order is paid
        if (!$order->isPaid()) {
            return $this->errorResponse('Pesanan belum dibayar', 422);
        }

        // Check if already has booking
        if ($order->booking) {
            return $this->errorResponse('Pesanan sudah dijadwalkan', 422);
        }

        // Check if schedule deadline expired (7 days after payment)
        if ($order->scheduleDeadlineExpired()) {
            return $this->errorResponse('Batas waktu memilih jadwal telah habis (7 hari setelah pembayaran)', 422);
        }

        $durationMinutes = $order->duration->minutes;
        $startTime = $request->start_time;
        $endTime = Carbon::parse($startTime)->addMinutes($durationMinutes)->format('H:i');

        // Check psikolog availability on that day
        if (!$this->bookingService->isPsikologAvailable($order->psikolog, $request->booking_date, $startTime, $endTime)) {
            return $this->errorResponse('Psikolog tidak tersedia pada jadwal yang dipilih', 422);
        }

        // Check slot conflict
        if ($this->bookingService->hasConflict($order->psikolog, $request->booking_date, $startTime, $endTime)) {
            return $this->errorResponse('Slot tidak tersedia, sudah dipesan orang lain', 422);
        }

        // Create booking
        $booking = Booking::create([
            'order_id' => $order->id,
            'pasien_id' => $order->pasien_id,
            'psikolog_id' => $order->psikolog_id,
            'booking_date' => $request->booking_date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_id' => 'room-' . Str::uuid()->toString(),
            'status' => 'confirmed',
        ]);

        // Update order status
        $order->update([
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        $booking->load(['order.category', 'order.duration', 'psikolog.psikologProfile']);

        return $this->successResponse(
            new BookingResource($booking),
            'Jadwal konsultasi berhasil dipilih. Detail telah dikirim.',
            201
        );
    }

    /**
     * List user's bookings.
     */
    public function index(Request $request)
    {
        $query = Booking::where('pasien_id', $request->user()->id)
            ->with(['order.category', 'order.duration', 'psikolog.psikologProfile', 'consultation']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->latest()->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse(
            $bookings,
            'Daftar booking konsultasi',
            BookingResource::class
        );
    }

    /**
     * Show booking detail.
     */
    public function show(Request $request, Booking $booking)
    {
        if ($booking->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $booking->load([
            'order.category',
            'order.duration',
            'psikolog.psikologProfile',
            'consultation',
            'rescheduleLogs',
        ]);

        return $this->successResponse(
            new BookingResource($booking),
            'Detail booking'
        );
    }

    /**
     * Reschedule booking (max 2x, min H-1).
     */
    public function reschedule(RescheduleBookingRequest $request, Booking $booking)
    {
        if ($booking->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        if (!$booking->canReschedule()) {
            return $this->errorResponse(
                'Booking tidak dapat dijadwalkan ulang. Maksimal 2x reschedule atau minimal H-1 sebelum jadwal.',
                422
            );
        }

        $durationMinutes = $booking->order->duration->minutes;
        $newStartTime = $request->start_time;
        $newEndTime = Carbon::parse($newStartTime)->addMinutes($durationMinutes)->format('H:i');

        // Check psikolog availability
        if (!$this->bookingService->isPsikologAvailable($booking->psikolog, $request->booking_date, $newStartTime, $newEndTime)) {
            return $this->errorResponse('Psikolog tidak tersedia pada jadwal baru', 422);
        }

        // Check conflict (exclude current booking)
        if ($this->bookingService->hasConflict($booking->psikolog, $request->booking_date, $newStartTime, $newEndTime, $booking->id)) {
            return $this->errorResponse('Slot baru tidak tersedia', 422);
        }

        // Log reschedule
        RescheduleLog::create([
            'booking_id' => $booking->id,
            'old_date' => $booking->booking_date,
            'old_start_time' => $booking->start_time,
            'old_end_time' => $booking->end_time,
            'new_date' => $request->booking_date,
            'new_start_time' => $newStartTime,
            'new_end_time' => $newEndTime,
            'reason' => $request->reason,
            'rescheduled_by' => 'pasien',
        ]);

        // Update booking
        $booking->update([
            'booking_date' => $request->booking_date,
            'start_time' => $newStartTime,
            'end_time' => $newEndTime,
            'status' => 'confirmed',
        ]);

        $booking->load(['order.category', 'order.duration', 'psikolog.psikologProfile', 'rescheduleLogs']);

        return $this->successResponse(
            new BookingResource($booking),
            'Jadwal berhasil diubah'
        );
    }

    /**
     * Cancel booking with refund calculation.
     */
    public function cancel(Request $request, Booking $booking)
    {
        if ($booking->pasien_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        if (!$booking->canCancel()) {
            return $this->errorResponse('Booking tidak dapat dibatalkan (sudah lewat jadwal)', 422);
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        // Calculate refund
        $consultationTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $booking->start_time);
        $refundAmount = $this->bookingService->calculateRefundAmount($booking->order, $consultationTime);
        $refundPercentage = $this->bookingService->getRefundPercentage($refundAmount, (float) $booking->order->calculated_price);

        // Create refund record (if refund > 0)
        $refund = null;
        if ($refundAmount > 0) {
            $refund = Refund::create([
                'order_id' => $booking->order_id,
                'amount' => $refundAmount,
                'reason' => $request->reason ?? 'Dibatalkan oleh pasien',
                'status' => 'pending',
            ]);
        }

        // Update booking
        $booking->update(['status' => 'cancelled']);

        // Update order
        $booking->order->update(['status' => 'cancelled']);

        $booking->load(['order.category', 'order.duration', 'psikolog.psikologProfile']);

        return $this->successResponse([
            'booking' => new BookingResource($booking),
            'refund' => $refund ? [
                'id' => $refund->id,
                'amount' => (float) $refund->amount,
                'percentage' => $refundPercentage,
                'status' => $refund->status,
            ] : null,
        ], 'Booking dibatalkan' . ($refundAmount > 0 ? '. Refund sedang diproses.' : ''));
    }
}