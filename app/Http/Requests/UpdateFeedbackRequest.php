<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            'comment' => ['sometimes', 'required', 'string', 'max:2000'],
        ];
    }
}
