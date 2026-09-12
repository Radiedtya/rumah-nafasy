<?php

namespace App\Http\Requests\Psikolog;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:10000'],
        ];
    }
}