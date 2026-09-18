<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreatePsikologRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Akun user
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],

            // Profil psikolog
            'specialization_id' => ['nullable', 'integer', 'exists:specializations,id'],
            'bio'               => ['nullable', 'string', 'max:2000'],
            'experience_years'  => ['required', 'integer', 'min:0', 'max:60'],
            'license_no'        => ['required', 'string', 'max:100', 'unique:psikolog_profiles,license_no'],
            'education'         => ['required', 'string', 'max:255'],
            'workplace'         => ['required', 'string', 'max:255'],
            'custom_rate'       => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'Nama psikolog wajib diisi',
            'email.required'            => 'Email wajib diisi',
            'email.unique'              => 'Email sudah digunakan',
            'phone.unique'              => 'Nomor telepon sudah digunakan',
            'password.required'         => 'Password wajib diisi',
            'password.min'              => 'Password minimal 8 karakter',
            'password.confirmed'        => 'Konfirmasi password tidak cocok',
            'experience_years.required' => 'Tahun pengalaman wajib diisi',
            'experience_years.integer'  => 'Tahun pengalaman harus berupa angka',
            'license_no.required'       => 'Nomor SIP wajib diisi',
            'license_no.unique'         => 'Nomor SIP sudah terdaftar',
            'education.required'        => 'Pendidikan terakhir wajib diisi',
            'workplace.required'        => 'Tempat praktik wajib diisi',
        ];
    }
}
