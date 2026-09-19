<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Psikolog\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
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
            $query->whereDate('booking_date', $request->date);
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

        $status = $request->status;

        // Tolak pengajuan wajib menyertakan alasan
        if ($status === 'rejected' && !$request->filled('rejected_reason')) {
            return $this->errorResponse('Alasan penolakan wajib diisi', 422);
        }

        // Transisi valid: hanya dari pending_psikolog boleh ke rejected
        if ($status === 'rejected' && !$booking->isPendingPsikolog()) {
            return $this->errorResponse('Hanya pengajuan yang menunggu persetujuan yang dapat ditolak', 422);
        }

        $booking->update([
            'status' => $status,
            'rejected_reason' => $status === 'rejected' ? $request->rejected_reason : null,
        ]);

        $booking->load(['order.category', 'order.duration', 'pasien', 'psikolog.psikologProfile']);

        // Notifikasi pasien hanya saat status benar-benar berubah
        if ($status === 'confirmed' && $booking->wasChanged('status')) {
            app(FonnteService::class)->notifyUser(
                $booking->pasien,
                WhatsAppMessages::bookingApproved($booking)
            );
        } elseif ($status === 'rejected' && $booking->wasChanged('status')) {
            app(FonnteService::class)->notifyUser(
                $booking->pasien,
                WhatsAppMessages::bookingRejected($booking)
            );
        }

        return $this->successResponse(
            new BookingResource($booking),
            'Status booking diperbarui'
        );
    }
}