<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Admin\CreatePsikologRequest;
use App\Http\Requests\Admin\UpdatePsikologRequest;
use App\Http\Resources\UserResource;
use App\Models\PsikologProfile;
use App\Models\User;
use App\Services\FonnteService;
use App\Support\WhatsAppMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PsikologController extends Controller
{
    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = User::role('psikolog')
            ->with(['psikologProfile.specialization', 'roles']);

        if ($request->filled('status')) {
            $query->whereHas('psikologProfile', fn($q) => $q->where('status', $request->status));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $psikolog = $query->paginate(min($request->get('per_page', 10), 50));

        return $this->paginateResponse($psikolog, 'Daftar psikolog', UserResource::class);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────

    public function show(User $user)
    {
        $user->load(['psikologProfile.specialization', 'roles']);
        return $this->successResponse(new UserResource($user), 'Detail psikolog');
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    public function store(CreatePsikologRequest $request)
    {
        $user = DB::transaction(function () use ($request) {
            // 1. Buat akun user
            $user = User::create([
                'name'               => $request->name,
                'email'              => $request->email,
                'phone'              => $request->phone,
                'password'           => Hash::make($request->password),
                'email_verified_at'  => now(),
                'phone_verified_at'  => $request->phone ? now() : null,
                'is_active'          => true,
            ]);

            // 2. Assign role psikolog
            $user->assignRole('psikolog');

            // 3. Buat profil psikolog (langsung verified karena dibuat oleh admin)
            PsikologProfile::create([
                'user_id'           => $user->id,
                'specialization_id' => $request->specialization_id,
                'slug'              => Str::slug($request->name) . '-' . Str::random(6),
                'bio'               => $request->bio,
                'experience_years'  => $request->experience_years,
                'license_no'        => $request->license_no,
                'education'         => $request->education,
                'workplace'         => $request->workplace,
                'custom_rate'       => $request->custom_rate,
                'status'            => 'verified',
                'verified_at'       => now(),
                'verified_by'       => $request->user()->id,
                'is_available'      => true,
            ]);

            return $user;
        });

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog berhasil didaftarkan',
            201
        );
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function update(UpdatePsikologRequest $request, User $user)
    {
        DB::transaction(function () use ($request, $user) {
            // Update data user
            $userData = array_filter([
                'name'  => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ], fn($v) => !is_null($v));

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            if (!empty($userData)) {
                $user->update($userData);
            }

            // Update profil psikolog
            $profileData = array_filter([
                'specialization_id' => $request->specialization_id,
                'bio'               => $request->bio,
                'experience_years'  => $request->experience_years,
                'license_no'        => $request->license_no,
                'education'         => $request->education,
                'workplace'         => $request->workplace,
                'custom_rate'       => $request->custom_rate,
                'is_available'      => $request->is_available,
            ], fn($v) => !is_null($v));

            if (!empty($profileData) && $user->psikologProfile) {
                $user->psikologProfile->update($profileData);
            }
        });

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Data psikolog berhasil diperbarui'
        );
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return $this->errorResponse('Tidak dapat menghapus akun admin', 403);
        }

        DB::transaction(function () use ($user) {
            $user->psikologProfile?->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        return $this->successResponse(null, 'Akun psikolog berhasil dihapus');
    }

    // ─── VERIFY ───────────────────────────────────────────────────────────────

    public function verify(Request $request, User $user)
    {
        $profile = $user->psikologProfile;
        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        $profile->update([
            'status'           => 'verified',
            'verified_at'      => now(),
            'verified_by'      => $request->user()->id,
            'is_available'     => true,
            'rejection_reason' => null,
        ]);

        app(FonnteService::class)->notifyUser($user, WhatsAppMessages::psikologVerified($user));

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog berhasil diverifikasi'
        );
    }

    // ─── SUSPEND ──────────────────────────────────────────────────────────────

    public function suspend(Request $request, User $user)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $profile = $user->psikologProfile;
        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        $profile->update([
            'status'           => 'suspended',
            'is_available'     => false,
            'rejection_reason' => $request->reason,
        ]);

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog ditangguhkan'
        );
    }

    // ─── ACTIVATE ─────────────────────────────────────────────────────────────

    public function activate(User $user)
    {
        $profile = $user->psikologProfile;
        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        $profile->update([
            'status'       => 'verified',
            'is_available' => true,
            'rejection_reason' => null,
        ]);

        return $this->successResponse(
            new UserResource($user->fresh()->load(['psikologProfile.specialization', 'roles'])),
            'Psikolog diaktifkan kembali'
        );
    }
}
