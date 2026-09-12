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

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email atau password salah', 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse('Akun Anda dinonaktifkan. Hubungi admin.', 403);
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