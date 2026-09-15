<?php

namespace App\Http\Controllers\Api\Psikolog;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Psikolog\UpdatePsikologProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $psikolog = $request->user()->load(['psikologProfile.specialization', 'roles']);

        return $this->successResponse(
            new UserResource($psikolog),
            'Profil psikolog'
        );
    }

    public function update(UpdatePsikologProfileRequest $request)
    {
        $user = $request->user();
        $profile = $user->psikologProfile;

        if (!$profile) {
            return $this->errorResponse('Profil psikolog tidak ditemukan', 404);
        }

        // Update user data
        $userData = $request->only(['name', 'phone']);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($userData);

        // Update profile data
        $profile->update($request->only(['bio', 'education', 'workplace']));

        $user->load(['psikologProfile.specialization', 'roles']);

        return $this->successResponse(
            new UserResource($user),
            'Profil diperbarui'
        );
    }
}