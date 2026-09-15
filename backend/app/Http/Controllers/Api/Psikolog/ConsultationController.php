<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Booking;
use App\Models\Consultation;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $consultations = Consultation::whereHas('booking', function ($q) use ($request) {
            $q->where('psikolog_id', $request->user()->id);
        })
        ->with(['booking.order.category', 'booking.pasien'])
        ->latest()
        ->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse($consultations, 'Daftar konsultasi', ConsultationResource::class);
    }

    public function show(Request $request, Consultation $consultation)
    {
        if ($consultation->booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $consultation->load(['booking.order.category', 'booking.pasien', 'notes']);

        return $this->successResponse(new ConsultationResource($consultation), 'Detail konsultasi');
    }

    public function start(Request $request, Booking $booking)
    {
        if ($booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        if (!$booking->isConfirmed()) {
            return $this->errorResponse('Booking tidak dapat dimulai (status: ' . $booking->status . ')', 422);
        }

        // Check if consultation already exists
        if ($booking->consultation) {
            return $this->errorResponse('Konsultasi sudah dimulai', 422);
        }

        $consultation = Consultation::create([
            'booking_id' => $booking->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        $booking->update(['status' => 'in_progress']);

        $consultation->load(['booking.order.category', 'booking.pasien']);

        return $this->successResponse(
            new ConsultationResource($consultation),
            'Konsultasi dimulai',
            201
        );
    }

    public function end(Request $request, Consultation $consultation)
    {
        if ($consultation->booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        if (!$consultation->isInProgress()) {
            return $this->errorResponse('Konsultasi tidak sedang berlangsung', 422);
        }

        $consultation->update([
            'ended_at' => now(),
            'status' => 'completed',
        ]);

        // Update booking & order
        $consultation->booking->update(['status' => 'completed']);
        $consultation->booking->order->update(['status' => 'completed']);

        // Update psikolog profile stats
        $profile = $request->user()->psikologProfile;
        if ($profile) {
            $profile->increment('total_consultations');
        }

        $consultation->load(['booking.order.category', 'booking.pasien', 'notes']);

        app(FonnteService::class)->notifyUser(
            $consultation->booking->pasien,
            WhatsAppMessages::consultationCompleted($consultation->booking)
        );

        return $this->successResponse(
            new ConsultationResource($consultation),
            'Konsultasi selesai'
        );
    }
}