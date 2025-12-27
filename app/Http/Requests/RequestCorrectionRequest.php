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
            'notes.required' => 'Catatan koreksi wajib diisi.',
            'notes.min' => 'Catatan koreksi harus terdiri dari minimal 10 karakter.',
            'due_date.after' => 'Batas waktu harus di masa depan.',
        ];
    }
}



