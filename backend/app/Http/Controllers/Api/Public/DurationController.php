<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\DurationResource;
use App\Models\DurationOption;

class DurationController extends Controller
{
    public function index()
    {
        $durations = DurationOption::where('is_active', true)
            ->orderBy('minutes', 'asc')
            ->get();

        return $this->successResponse(
            DurationResource::collection($durations),
            'Daftar pilihan durasi'
        );
    }
}