<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Services\JitsiService;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function __construct(
        private JitsiService $jitsiService
    ) {}

    /**
     * Get meeting info for a booking.
     * Accessible by both pasien and psikolog of the booking.
     */
    public function show(Request $request, Booking $booking)
    {
        // Authorization: must be pasien or psikolog of this booking
        if (
            $booking->pasien_id !== $request->user()->id &&
            $booking->psikolog_id !== $request->user()->id
        ) {
            return $this->errorResponse('Anda tidak memiliki akses ke konsultasi ini', 403);
        }

        // Check booking status
        if (!in_array($booking->status, ['confirmed', 'in_progress'])) {
            return $this->errorResponse(
                'Konsultasi tidak dapat diakses (status: ' . $booking->status . ')',
                422
            );
        }

        $meetingInfo = $this->jitsiService->getMeetingInfo($booking, $request->user());

        return $this->successResponse(
            array_merge($meetingInfo, [
                'booking' => [
                    'id' => $booking->id,
                    'booking_date' => $booking->booking_date?->format('Y-m-d'),
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'status' => $booking->status,
                    'psikolog_name' => $booking->psikolog?->name,
                    'pasien_name' => $booking->pasien?->name,
                    'consultation_type' => $booking->order?->consultation_type,
                ],
            ]),
            'Meeting info'
        );
    }
}