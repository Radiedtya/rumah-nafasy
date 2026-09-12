<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class PsikologController extends Controller
{
    public function index(Request $request)
    {
        $query = User::role('psikolog')
            ->with(['psikologProfile.specialization', 'roles']);

        if ($request->filled('status')) {
            $query->whereHas('psikologProfile', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $psikolog = $query->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse($psikolog, 'Daftar psikolog', UserResource::class);
    }

    public function show(Request $request, User $user)
    {
        $user->load(['psikologProfile.specialization', 'roles']);

        return $this->successResponse(new UserResource($user), 'Detail psikolog');
    }

    public function verify(Request $request, User $user)
    {
        $profile = $user->psikologProfile;

        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        $profile->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
            'is_available' => true,
            'rejection_reason' => null,
        ]);

        // TODO: Send WA notification

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog berhasil diverifikasi'
        );
    }

    public function suspend(Request $request, User $user)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $user->psikologProfile;

        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        $profile->update([
            'status' => 'suspended',
            'is_available' => false,
            'rejection_reason' => $request->reason,
        ]);

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog ditangguhkan'
        );
    }

    public function activate(Request $request, User $user)
    {
        $profile = $user->psikologProfile;

        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        $profile->update([
            'status' => 'verified',
            'is_available' => true,
            'rejection_reason' => null,
        ]);

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog diaktifkan kembali'
        );
    }
}