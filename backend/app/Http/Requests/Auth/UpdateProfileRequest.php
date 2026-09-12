<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', "unique:users,phone,{$userId}"],
            'avatar' => ['sometimes', 'image', 'max:2048'],
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Nomor HP sudah digunakan',
            'avatar.image' => 'File harus berupa gambar',
            'avatar.max' => 'Ukuran gambar maksimal 2MB',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'current_password.required_with' => 'Password lama wajib diisi untuk mengganti password',
        ];
    }
}