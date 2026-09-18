<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ConsultationResource;
use App\Models\Consultation;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    /**
     * Semua konsultasi lintas psikolog — untuk admin.
     */
    public function index(Request $request)
    {
        $query = Consultation::with([
            'booking.pasien',
            'booking.psikolog.psikologProfile.specialization',
            'booking.order.category',
            'booking.order.duration',
            'notes',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('psikolog_id')) {
            $query->whereHas('booking', fn($q) => $q->where('psikolog_id', $request->psikolog_id));
        }

        if ($request->filled('search')) {
            $query->whereHas('booking.pasien', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'))
                  ->orWhereHas('booking.psikolog', fn($q) => $q->where('name', 'like', '%' . $request->search . '%'));
        }

        $consultations = $query->latest()->paginate(min($request->get('per_page', 15), 50));

        return $this->paginateResponse($consultations, 'Semua konsultasi', ConsultationResource::class);
    }

    public function show(Consultation $consultation)
    {
        $consultation->load([
            'booking.pasien',
            'booking.psikolog.psikologProfile.specialization',
            'booking.order.category',
            'booking.order.duration',
            'notes',
        ]);

        return $this->successResponse(new ConsultationResource($consultation), 'Detail konsultasi');
    }
}
