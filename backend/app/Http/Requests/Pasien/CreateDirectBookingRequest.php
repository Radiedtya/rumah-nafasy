<?php

namespace App\Http\Requests\Pasien;

use Illuminate\Foundation\Http\FormRequest;

class CreateDirectBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'psikolog_id' => ['required', 'exists:users,id'],
            'consultation_type' => ['required', 'in:video,offline'],
            'duration_minutes' => ['required', 'integer', 'in:30,60,90'],
            'requested_category_id' => ['nullable', 'exists:client_categories,id'],
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'psikolog_id.required' => 'Psikolog wajib dipilih',
            'psikolog_id.exists' => 'Psikolog tidak ditemukan',
            'consultation_type.required' => 'Jenis konsultasi wajib dipilih',
            'consultation_type.in' => 'Jenis konsultasi harus video call atau offline (tatap muka)',
            'duration_minutes.required' => 'Durasi sesi wajib dipilih',
            'duration_minutes.in' => 'Durasi sesi harus 30, 60, atau 90 menit',
            'booking_date.required' => 'Tanggal konsultasi wajib dipilih',
            'booking_date.after_or_equal' => 'Tanggal tidak boleh masa lalu',
            'start_time.required' => 'Jam konsultasi wajib dipilih',
            'start_time.date_format' => 'Format jam tidak valid (HH:MM)',
            'note.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
