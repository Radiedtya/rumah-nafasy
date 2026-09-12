<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\DurationResource;
use App\Models\DurationOption;
use Illuminate\Http\Request;

class DurationController extends Controller
{
    public function index()
    {
        $durations = DurationOption::withCount('orders')->orderBy('minutes', 'asc')->get();

        return $this->successResponse(DurationResource::collection($durations), 'Daftar durasi');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'minutes' => ['required', 'integer', 'min:1', 'unique:duration_options,minutes'],
            'multiplier' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $duration = DurationOption::create([
            'name' => $request->name,
            'minutes' => $request->minutes,
            'multiplier' => $request->multiplier,
            'is_active' => $request->get('is_active', true),
        ]);

        return $this->successResponse(new DurationResource($duration), 'Durasi dibuat', 201);
    }

    public function show(DurationOption $duration)
    {
        $duration->loadCount('orders');

        return $this->successResponse(new DurationResource($duration), 'Detail durasi');
    }

    public function update(Request $request, DurationOption $duration)
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'minutes' => ['sometimes', 'integer', 'min:1', 'unique:duration_options,minutes,' . $duration->id],
            'multiplier' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $duration->update($request->only(['name', 'minutes', 'multiplier', 'is_active']));

        return $this->successResponse(new DurationResource($duration->fresh()), 'Durasi diperbarui');
    }

    public function destroy(DurationOption $duration)
    {
        if ($duration->orders()->exists()) {
            return $this->errorResponse('Durasi tidak dapat dihapus, masih digunakan oleh pesanan', 422);
        }

        $duration->delete();

        return $this->successResponse(null, 'Durasi dihapus');
    }
}