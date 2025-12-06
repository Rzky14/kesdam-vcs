<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('request_correction');
    }

    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'min:10', 'max:1000'],
            'due_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Correction notes are required.',
            'notes.min' => 'Correction notes must be at least 10 characters.',
            'due_date.after' => 'Due date must be in the future.',
        ];
    }
}
