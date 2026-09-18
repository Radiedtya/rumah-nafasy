<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        return $this->successResponse(
            new UserResource($request->user()->load('roles', 'psikologProfile')),
            'Data user'
        );
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->only(['name', 'phone']);

        // Jika phone dikirim sebagai string kosong, simpan null
        if (array_key_exists('phone', $data) && $data['phone'] === '') {
            $data['phone'] = null;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Handle password change
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse('Password lama salah', 422);
            }
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $this->successResponse(
            new UserResource($user->load('roles', 'psikologProfile')),
            'Profil berhasil diperbarui'
        );
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ], [
            'avatar.required' => 'File gambar wajib disertakan',
            'avatar.image'    => 'File harus berupa gambar',
            'avatar.max'      => 'Ukuran gambar maksimal 2MB',
        ]);

        $user = $request->user();

        // Hapus avatar lama jika ada
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update([
            'avatar' => $request->file('avatar')->store('avatars', 'public'),
        ]);

        return $this->successResponse(
            new UserResource($user->fresh()->load('roles', 'psikologProfile')),
            'Foto profil berhasil diperbarui'
        );
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if (!$user->avatar) {
            return $this->errorResponse('Tidak ada foto profil untuk dihapus', 422);
        }

        Storage::disk('public')->delete($user->avatar);
        $user->update(['avatar' => null]);

        return $this->successResponse(
            new UserResource($user->load('roles', 'psikologProfile')),
            'Foto profil berhasil dihapus'
        );
    }
}