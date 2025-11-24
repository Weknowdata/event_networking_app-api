<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectAttendeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'attendee_id' => ['required', 'string'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'signature' => ['required', 'string', 'max:255'],
        ];
    }

    public function attendeeId(): int
    {
        return (int) $this->string('attendee_id')->value();
    }

    public function notes(): ?string
    {
        $notes = $this->input('notes');

        // Normalize empty strings to null so callers can treat absence and blank the same way.
        return $notes !== null && $notes !== '' ? (string) $notes : null;
    }
}
