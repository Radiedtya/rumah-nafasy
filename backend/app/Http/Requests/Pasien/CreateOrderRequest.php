<?php

namespace App\Http\Requests\Pasien;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'psikolog_id' => ['required', 'exists:users,id'],
            'category_id' => ['required', 'exists:client_categories,id'],
            'duration_id' => ['required', 'exists:duration_options,id'],
            'consultation_type' => ['required', 'in:video,chat'],
        ];
    }

    public function messages(): array
    {
        return [
            'psikolog_id.required' => 'Psikolog wajib dipilih',
            'psikolog_id.exists' => 'Psikolog tidak ditemukan',
            'category_id.required' => 'Kategori konsultasi wajib dipilih',
            'category_id.exists' => 'Kategori tidak ditemukan',
            'duration_id.required' => 'Durasi konsultasi wajib dipilih',
            'duration_id.exists' => 'Durasi tidak ditemukan',
            'consultation_type.required' => 'Tipe konsultasi wajib dipilih',
            'consultation_type.in' => 'Tipe konsultasi harus video atau chat',
        ];
    }
}