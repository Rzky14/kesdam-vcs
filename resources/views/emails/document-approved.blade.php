@component('mail::message')
# Dokumen Disetujui

Halo {{ $document->creator->name }},

Dokumen Anda telah disetujui!

**Dokumen:** {{ $document->subject }}  
**Disetujui oleh:** {{ $approver->name }} ({{ $approver->position }})  
**Tanggal Persetujuan:** {{ now()->format('d M Y H:i') }}

@component('mail::button', ['url' => $documentUrl])
Lihat Dokumen
@endcomponent

Dokumen Anda telah berhasil diproses melalui semua tahapan persetujuan dan sekarang tersimpan di sistem.

Terima kasih,  
**KESDAM VCS System**
@endcomponent
