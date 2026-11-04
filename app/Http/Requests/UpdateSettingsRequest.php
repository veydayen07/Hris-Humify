<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_timezone' => ['sometimes', 'string', 'timezone'],
            'face_match_threshold' => ['sometimes', 'numeric', 'between:0,100'],
            'close_day_time' => ['sometimes', 'date_format:H:i'],
        ];
    }
}

