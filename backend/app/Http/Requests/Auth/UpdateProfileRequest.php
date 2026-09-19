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
            'phone' => ['nullable', 'string', 'max:20', "unique:users,phone,{$userId}"],
            'avatar' => ['sometimes', 'image', 'max:2048'],
            // password / current_password TIDAK lagi diterima di sini —
            // perubahan kredensial lewat PUT auth/password (PasswordController).
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Nomor HP sudah digunakan',
            'avatar.image' => 'File harus berupa gambar',
            'avatar.max' => 'Ukuran gambar maksimal 2MB',
        ];
    }
}
