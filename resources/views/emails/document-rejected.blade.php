@component('mail::message')
# Dokumen Ditolak

Halo {{ $document->creator->name }},

Dokumen Anda telah ditolak dan memerlukan revisi.

**Dokumen:** {{ $document->subject }}  
**Ditolak oleh:** {{ $rejector->name }} ({{ $rejector->position }})  
**Tanggal Penolakan:** {{ now()->format('d M Y H:i') }}

**Alasan Penolakan:**
{{ $reason }}

@component('mail::button', ['url' => $documentUrl])
Lihat Dokumen & Revisi
@endcomponent

Silakan lakukan revisi sesuai dengan alasan penolakan di atas dan ajukan kembali untuk persetujuan.

Terima kasih,  
**KESDAM VCS System**
@endcomponent
