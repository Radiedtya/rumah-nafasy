<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\SpecializationResource;
use App\Models\Specialization;
// use Illuminate\Http\Request;

class SpecializationController extends Controller
{
    public function index()
    {
        $specializations = Specialization::where('is_active', true)
            ->orderBy('name')
            ->get();

        return $this->successResponse(
            SpecializationResource::collection($specializations),
            'Daftar spesialisasi'
        );
    }

    public function show(string $slug)
    {
        $specialization = Specialization::where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (!$specialization) {
            return $this->errorResponse('Spesialisasi tidak ditemukan', 404);
        }

        return $this->successResponse(
            new SpecializationResource($specialization),
            'Detail spesialisasi'
        );
    }
}