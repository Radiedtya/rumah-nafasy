<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePsikologRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;
        $profileId = $this->route('user')?->psikologProfile?->id;

        return [
            'name'              => ['sometimes', 'string', 'max:255'],
            'email'             => ['sometimes', 'email', 'max:255', "unique:users,email,{$userId}"],
            'phone'             => ['nullable', 'string', 'max:20', "unique:users,phone,{$userId}"],
            'password'          => ['nullable', 'string', 'min:8', 'confirmed'],

            'specialization_id' => ['nullable', 'integer', 'exists:specializations,id'],
            'bio'               => ['nullable', 'string', 'max:2000'],
            'experience_years'  => ['sometimes', 'integer', 'min:0', 'max:60'],
            'license_no'        => ['sometimes', 'string', 'max:100', "unique:psikolog_profiles,license_no,{$profileId}"],
            'education'         => ['sometimes', 'string', 'max:255'],
            'workplace'         => ['sometimes', 'string', 'max:255'],
            'custom_rate'       => ['nullable', 'numeric', 'min:0'],
            'is_available'      => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Email sudah digunakan',
            'phone.unique'       => 'Nomor telepon sudah digunakan',
            'password.min'       => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'license_no.unique'  => 'Nomor SIP sudah terdaftar',
        ];
    }
}
