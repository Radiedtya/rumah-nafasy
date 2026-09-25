<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Semua booking lintas psikolog — untuk admin.
     */
    public function index(Request $request)
    {
        $query = Booking::with([
            'pasien',
            'psikolog.psikologProfile.specialization',
            'order.category',
            'order.duration',
            'requestedCategory',
            'consultation',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->date);
        }

        if ($request->filled('psikolog_id')) {
            $query->where('psikolog_id', $request->psikolog_id);
        }

        if ($request->filled('search')) {
            $query->whereHas('pasien', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
                  ->orWhereHas('psikolog', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        $bookings = $query->latest('booking_date')
                          ->paginate(min($request->get('per_page', 15), 50));

        return $this->paginateResponse($bookings, 'Semua jadwal booking', BookingResource::class);
    }

    public function show(Booking $booking)
    {
        $booking->load([
            'pasien',
            'psikolog.psikologProfile.specialization',
            'order.category',
            'order.duration',
            'consultation.notes',
            'rescheduleLogs',
        ]);

        return $this->successResponse(new BookingResource($booking), 'Detail booking');
    }

    /**
     * Admin bisa update status booking (misal: batalkan secara paksa).
     */
    public function updateStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:confirmed,cancelled,completed,no_show'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $booking->update(['status' => $request->status]);

        $booking->load(['pasien', 'psikolog', 'order.category', 'order.duration', 'consultation']);

        return $this->successResponse(new BookingResource($booking), 'Status booking diperbarui');
    }
}
