<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->assignRole('pasien');

        $token = $user->createToken('register-token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registrasi berhasil. Selamat datang di Rumah Natasy!', 201);
    }
}