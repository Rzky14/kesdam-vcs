<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('approve_documents');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Rejection reason is required.',
            'reason.min' => 'Rejection reason must be at least 10 characters.',
        ];
    }
}
