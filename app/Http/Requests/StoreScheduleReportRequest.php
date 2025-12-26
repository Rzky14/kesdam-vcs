<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleReportRequest extends FormRequest
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
            'schedule_types' => ['nullable', 'array'],
            'schedule_types.*' => ['in:Jadwal Dukkes,Jadwal Jaga,Jadwal Kegiatan Satuan'],
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
            'schedule_types.array' => 'Jenis jadwal harus berupa array.',
            'schedule_types.*.in' => 'Jenis jadwal yang dipilih tidak valid.',
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
