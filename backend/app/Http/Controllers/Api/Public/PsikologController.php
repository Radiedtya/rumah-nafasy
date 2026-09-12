<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\PsikologResource;
use App\Models\User;
use Illuminate\Http\Request;

class PsikologController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('psikolog')
            ->whereHas('psikologProfile', function ($q) {
                $q->where('status', 'verified')
                  ->where('is_available', true);
            })
            ->with(['psikologProfile.specialization']);

        // Filter by specialization
        if ($request->filled('specialization')) {
            $query->whereHas('psikologProfile.specialization', function ($q) use ($request) {
                $q->where('slug', $request->specialization);
            });
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort
        $sort = $request->get('sort', 'rating');
        $query->join('psikolog_profiles', 'users.id', '=', 'psikolog_profiles.user_id');

        switch ($sort) {
            case 'experience':
                $query->orderByDesc('psikolog_profiles.experience_years');
                break;
            case 'name':
                $query->orderBy('users.name');
                break;
            case 'rating':
            default:
                $query->orderByDesc('psikolog_profiles.rating_avg');
                break;
        }

        // Remove duplicate columns
        $query->select('users.*');

        $perPage = min($request->get('per_page', 10), 50);
        $psikolog = $query->paginate($perPage);

        return $this->paginateResponse(
            $psikolog,
            'Daftar psikolog terverifikasi',
            PsikologResource::class
        );
    }

    public function show(string $slug)
    {
        $psikolog = User::role('psikolog')
            ->whereHas('psikologProfile', function ($q) use ($slug) {
                $q->where('slug', $slug)
                  ->where('status', 'verified');
            })
            ->with([
                'psikologProfile.specialization',
                'psikologProfile.schedules' => function ($q) {
                    $q->where('is_available', true)->orderBy('day_of_week');
                },
            ])
            ->first();

        if (!$psikolog) {
            return $this->errorResponse('Psikolog tidak ditemukan', 404);
        }

        return $this->successResponse(
            new PsikologResource($psikolog),
            'Detail psikolog'
        );
    }
}