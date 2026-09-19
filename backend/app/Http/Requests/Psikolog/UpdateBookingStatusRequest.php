<?php

namespace App\Http\Requests\Psikolog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:confirmed,in_progress,completed,cancelled,rejected'],
            'rejected_reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}