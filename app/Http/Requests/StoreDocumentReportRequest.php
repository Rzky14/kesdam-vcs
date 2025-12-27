<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentReportRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan ini.
     */
    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date', 'before_or_equal:period_end'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'classifications' => ['nullable', 'array'],
            'classifications.*' => ['in:Biasa,Rahasia,Telegram'],
            'document_status' => ['nullable', 'in:pending,approved,rejected,archived'],
        ];
    }

    /**
     * Dapatkan pesan kesalahan untuk aturan validasi yang didefinisikan.
     */
    public function messages(): array
    {
        return [
            'period_start.required' => 'Tanggal periode mulai harus diisi.',
            'period_start.date' => 'Format tanggal periode mulai tidak valid.',
            'period_start.before_or_equal' => 'Periode mulai harus sebelum atau sama dengan periode selesai.',
            'period_end.required' => 'Tanggal periode selesai harus diisi.',
            'period_end.date' => 'Format tanggal periode selesai tidak valid.',
            'period_end.after_or_equal' => 'Periode selesai harus setelah atau sama dengan periode mulai.',
            'classifications.array' => 'Klasifikasi harus berupa array.',
            'classifications.*.in' => 'Klasifikasi yang dipilih tidak valid.',
            'document_status.in' => 'Status dokumen yang dipilih tidak valid.',
        ];
    }

    /**
     * Siapkan data untuk validasi.
     */
    protected function prepareForValidation(): void
    {
        // Pastikan period_start sebelum period_end
        if ($this->has('period_start') && $this->has('period_end')) {
            $start = \Carbon\Carbon::createFromFormat('Y-m-d', $this->period_start);
            $end = \Carbon\Carbon::createFromFormat('Y-m-d', $this->period_end);

            if ($start > $end) {
                $this->merge([
                    'period_end' => $this->period_start,
                    'period_start' => $this->period_end,
                ]);
            }
        }
    }
}



