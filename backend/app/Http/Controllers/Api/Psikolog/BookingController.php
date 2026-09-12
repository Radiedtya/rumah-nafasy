<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Psikolog\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::where('psikolog_id', $request->user()->id)
            ->with(['order.category', 'order.duration', 'pasien', 'consultation']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->where('booking_date', $request->date);
        }

        $bookings = $query->latest('booking_date')->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse($bookings, 'Daftar booking', BookingResource::class);
    }

    public function show(Request $request, Booking $booking)
    {
        if ($booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $booking->load(['order.category', 'order.duration', 'pasien', 'consultation.notes', 'rescheduleLogs']);

        return $this->successResponse(new BookingResource($booking), 'Detail booking');
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking)
    {
        if ($booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $booking->update(['status' => $request->status]);

        $booking->load(['order.category', 'order.duration', 'pasien']);

        return $this->successResponse(
            new BookingResource($booking),
            'Status booking diperbarui'
        );
    }
}