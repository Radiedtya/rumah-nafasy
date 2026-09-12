<?php

namespace App\Http\Requests\Psikolog;

use Illuminate\Foundation\Http\FormRequest;

class CreateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'day_of_week.between' => 'Hari harus 0 (Ahad) - 6 (Sabtu)',
            'end_time.after' => 'Jam selesai harus setelah jam mulai',
        ];
    }
}