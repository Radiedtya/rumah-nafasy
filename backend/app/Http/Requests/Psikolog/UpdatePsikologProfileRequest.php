<?php

namespace App\Http\Requests\Psikolog;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePsikologProfileRequest extends FormRequest
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
            'bio' => ['sometimes', 'string', 'max:2000'],
            'education' => ['sometimes', 'string', 'max:255'],
            'workplace' => ['sometimes', 'string', 'max:255'],
        ];
    }
}