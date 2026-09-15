<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Psikolog\StoreConsultationNoteRequest;
use App\Http\Resources\ConsultationNoteResource;
use App\Models\Consultation;
use App\Models\ConsultationNote;
use Illuminate\Http\Request;

class ConsultationNoteController extends Controller
{
    public function index(Request $request, Consultation $consultation)
    {
        if ($consultation->booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $notes = $consultation->notes()->with('psikolog')->latest()->get();

        return $this->successResponse(
            ConsultationNoteResource::collection($notes),
            'Daftar catatan konsultasi'
        );
    }

    public function store(StoreConsultationNoteRequest $request, Consultation $consultation)
    {
        if ($consultation->booking->psikolog_id !== $request->user()->id) {
            return $this->errorResponse('Anda tidak memiliki akses', 403);
        }

        $note = ConsultationNote::create([
            'consultation_id' => $consultation->id,
            'psikolog_id' => $request->user()->id,
            'content' => $request->content, // auto-encrypted by model cast
        ]);

        $note->load('psikolog');

        return $this->successResponse(
            new ConsultationNoteResource($note),
            'Catatan konsultasi disimpan',
            201
        );
    }
}