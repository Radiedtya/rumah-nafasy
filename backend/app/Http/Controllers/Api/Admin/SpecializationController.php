<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\SpecializationResource;
use App\Models\Specialization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SpecializationController extends Controller
{
    public function index()
    {
        $specializations = Specialization::withCount('psikologProfiles')->orderBy('name')->get();

        return $this->successResponse(SpecializationResource::collection($specializations), 'Daftar spesialisasi');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:specializations,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $spec = Specialization::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'icon' => $request->icon,
            'is_active' => $request->get('is_active', true),
        ]);

        return $this->successResponse(new SpecializationResource($spec), 'Spesialisasi dibuat', 201);
    }

    public function show(Specialization $specialization)
    {
        $specialization->loadCount('psikologProfiles');

        return $this->successResponse(new SpecializationResource($specialization), 'Detail spesialisasi');
    }

    public function update(Request $request, Specialization $specialization)
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255', 'unique:specializations,name,' . $specialization->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data = $request->only(['name', 'description', 'icon', 'is_active']);

        if ($request->filled('name')) {
            $data['slug'] = Str::slug($request->name);
        }

        $specialization->update($data);

        return $this->successResponse(new SpecializationResource($specialization->fresh()), 'Spesialisasi diperbarui');
    }

    public function destroy(Specialization $specialization)
    {
        if ($specialization->psikologProfiles()->exists()) {
            return $this->errorResponse('Spesialisasi tidak dapat dihapus, masih digunakan oleh psikolog', 422);
        }

        $specialization->delete();

        return $this->successResponse(null, 'Spesialisasi dihapus');
    }
}