<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('approve_documents');
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:1000'],
            'signature' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'signature.mimes' => 'Berkas tanda tangan harus berformat PDF, JPG, JPEG, atau PNG.',
            'signature.max' => 'Berkas tanda tangan tidak boleh lebih dari 5MB.',
        ];
    }
}



