<?php

namespace App\Http\Requests\Pasien;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'booking_date.required' => 'Tanggal konsultasi wajib dipilih',
            'booking_date.date' => 'Format tanggal tidak valid',
            'booking_date.after_or_equal' => 'Tanggal tidak boleh masa lalu',
            'start_time.required' => 'Jam konsultasi wajib dipilih',
            'start_time.date_format' => 'Format jam tidak valid (HH:MM)',
        ];
    }
}