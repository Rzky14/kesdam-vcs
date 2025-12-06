@component('mail::message')
# Persetujuan Dokumen Diperlukan

Halo {{ $approver->name }},

Dokumen berikut memerlukan persetujuan Anda:

**Dokumen:** {{ $document->subject }}  
**Tipe:** {{ $document->type }}  
**Klasifikasi:** {{ $document->classification }}  
**Dibuat oleh:** {{ $document->creator->name }} ({{ $document->creator->position }})  
**Tanggal:** {{ $document->created_at->format('d M Y H:i') }}

@component('mail::button', ['url' => $approvalUrl])
Lihat Dokumen & Setujui
@endcomponent

Silakan klik tombol di atas untuk melihat detail dokumen dan memberikan persetujuan Anda.

Terima kasih,  
**KESDAM VCS System**
@endcomponent
