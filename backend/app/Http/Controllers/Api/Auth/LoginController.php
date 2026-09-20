<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // User khusus-Google punya password NULL — Hash::check di atasnya
        // akan error/crash; perlakukan sebagai kredensial salah.
        if (! $user || ! $user->password || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email atau password salah', 401);
        }

        if (! $user->is_active) {
            return $this->errorResponse('Akun Anda dinonaktifkan. Hubungi admin.', 403);
        }

        // ── Gerbang verifikasi email ─────────────────────────────────────
        // Pendaftar metode email harus verifikasi OTP sebelum bisa login.
        // error code `email_unverified` dipakai frontend untuk mengarahkan
        // user ke halaman OTP. Pesan tidak membocorkan status lain.
        if (! $user->email_verified_at) {
            return $this->errorResponse(
                'Email Anda belum diverifikasi. Masukkan kode verifikasi yang dikirim ke email Anda.',
                403,
                ['code' => ['email_unverified']]
            );
        }

        $deviceName = $request->device_name ?? 'auth-token';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user->load('roles', 'psikologProfile')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login berhasil');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil');
    }
}
