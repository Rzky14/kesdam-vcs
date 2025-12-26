<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Model Dokumen
 * 
 * Representasi dokumen dalam sistem KESDAM VCS.
 * Mendukung berbagai jenis dokumen: surat masuk, surat keluar, rahasia, telegram.
 * 
 * @property int $id
 * @property string $type Jenis dokumen (masuk/keluar)
 * @property string $classification Klasifikasi (biasa/rahasia/telegram)
 * @property string $number Nomor dokumen
 * @property \Carbon\Carbon $date Tanggal dokumen
 * @property string $sender Pengirim
 * @property string $recipient Penerima
 * @property string $subject Perihal
 * @property string $description Deskripsi
 * @property array $attachments Lampiran
 * @property string $status Status dokumen
 * @property string $priority Prioritas
 * @property bool $is_encrypted Apakah terenkripsi
 * @property \Carbon\Carbon $archived_at Tanggal diarsipkan
 * @property int $created_by Dibuat oleh
 * @property int $updated_by Diperbarui oleh
 */
class Dokumen extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'classification',
        'number',
        'date',
        'sender',
        'recipient',
        'subject',
        'description',
        'attachments',
        'status',
        'priority',
        'is_encrypted',
        'archived_at',
        'created_by',
        'updated_by',
    ];

    /**
     * Atribut yang harus di-cast ke tipe tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'archived_at' => 'date',
        'attachments' => 'array',
        'is_encrypted' => 'boolean',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'is_encrypted',
    ];

    // ==========================================
    // RELASI (RELATIONSHIPS)
    // ==========================================

    /**
     * Mendapatkan pembuat dokumen.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pembuat()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias untuk pembuat() - kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function creator()
    {
        return $this->pembuat();
    }

    /**
     * Mendapatkan pengubah terakhir dokumen.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pengubah()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Alias untuk pengubah() - kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updater()
    {
        return $this->pengubah();
    }

    /**
     * Mendapatkan semua riwayat persetujuan untuk dokumen ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function riwayatPersetujuan()
    {
        return $this->hasMany(ApprovalHistory::class, 'document_id');
    }

    /**
     * Alias untuk riwayatPersetujuan() - kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvalHistories()
    {
        return $this->riwayatPersetujuan();
    }

    /**
     * Mendapatkan semua permintaan koreksi untuk dokumen ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permintaanKoreksi()
    {
        return $this->hasMany(CorrectionRequest::class, 'document_id');
    }

    /**
     * Alias untuk permintaanKoreksi() - kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function correctionRequests()
    {
        return $this->permintaanKoreksi();
    }

    /**
     * Mendapatkan semua batas waktu persetujuan untuk dokumen ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function batasWaktuPersetujuan()
    {
        return $this->hasMany(ApprovalDeadline::class, 'document_id');
    }

    /**
     * Alias untuk batasWaktuPersetujuan() - kompatibilitas.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvalDeadlines()
    {
        return $this->batasWaktuPersetujuan();
    }

    // ==========================================
    // HELPER METHODS - STATUS PERSETUJUAN
    // ==========================================

    /**
     * Mendapatkan level persetujuan saat ini.
     *
     * @return int
     */
    public function ambilLevelPersetujuanSaatIni(): int
    {
        return $this->riwayatPersetujuan()
            ->orderBy('approval_level', 'desc')
            ->value('approval_level') ?? 0;
    }

    /**
     * Alias untuk ambilLevelPersetujuanSaatIni() - kompatibilitas.
     *
     * @return int
     */
    public function getCurrentApprovalLevel(): int
    {
        return $this->ambilLevelPersetujuanSaatIni();
    }

    /**
     * Mendapatkan aksi persetujuan terakhir.
     *
     * @return \App\Models\ApprovalHistory|null
     */
    public function ambilAksiPersetujuanTerakhir()
    {
        return $this->riwayatPersetujuan()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Alias untuk ambilAksiPersetujuanTerakhir() - kompatibilitas.
     *
     * @return \App\Models\ApprovalHistory|null
     */
    public function getLastApprovalAction()
    {
        return $this->ambilAksiPersetujuanTerakhir();
    }

    /**
     * Cek apakah dokumen memiliki koreksi yang tertunda.
     *
     * @return bool
     */
    public function punyaKoreksiTertunda(): bool
    {
        return $this->permintaanKoreksi()
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Alias untuk punyaKoreksiTertunda() - kompatibilitas.
     *
     * @return bool
     */
    public function hasPendingCorrections(): bool
    {
        return $this->punyaKoreksiTertunda();
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope untuk filter berdasarkan jenis dokumen.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $jenis
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBerdasarkanJenis($query, $jenis)
    {
        return $query->where('type', $jenis);
    }

    /**
     * Alias untuk scopeBerdasarkanJenis() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $this->scopeBerdasarkanJenis($query, $type);
    }

    /**
     * Scope untuk filter berdasarkan klasifikasi.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $klasifikasi
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBerdasarkanKlasifikasi($query, $klasifikasi)
    {
        return $query->where('classification', $klasifikasi);
    }

    /**
     * Alias untuk scopeBerdasarkanKlasifikasi() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $classification
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfClassification($query, $classification)
    {
        return $this->scopeBerdasarkanKlasifikasi($query, $classification);
    }

    /**
     * Scope untuk filter berdasarkan status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDenganStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Alias untuk scopeDenganStatus() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $this->scopeDenganStatus($query, $status);
    }

    /**
     * Scope untuk dokumen draft saja.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraf($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Alias untuk scopeDraf() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $this->scopeDraf($query);
    }

    /**
     * Scope untuk dokumen menunggu persetujuan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMenungguPersetujuan($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Alias untuk scopeMenungguPersetujuan() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingApproval($query)
    {
        return $this->scopeMenungguPersetujuan($query);
    }

    /**
     * Scope untuk dokumen yang sudah disetujui.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDisetujui($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Alias untuk scopeDisetujui() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $this->scopeDisetujui($query);
    }

    /**
     * Scope untuk dokumen yang diarsipkan.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDiarsipkan($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Alias untuk scopeDiarsipkan() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $this->scopeDiarsipkan($query);
    }

    /**
     * Scope untuk filter berdasarkan rentang tanggal.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $tanggalMulai
     * @param  string  $tanggalSelesai
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRentangTanggal($query, $tanggalMulai, $tanggalSelesai)
    {
        return $query->whereBetween('date', [$tanggalMulai, $tanggalSelesai]);
    }

    /**
     * Alias untuk scopeRentangTanggal() - kompatibilitas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $startDate
     * @param  string  $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $this->scopeRentangTanggal($query, $startDate, $endDate);
    }

    // ==========================================
    // HELPER METHODS - CEK STATUS
    // ==========================================

    /**
     * Cek apakah dokumen adalah draft.
     *
     * @return bool
     */
    public function adalahDraf(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Alias untuk adalahDraf() - kompatibilitas.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->adalahDraf();
    }

    /**
     * Cek apakah dokumen menunggu persetujuan.
     *
     * @return bool
     */
    public function adalahMenungguPersetujuan(): bool
    {
        return $this->status === 'pending_approval';
    }

    /**
     * Alias untuk adalahMenungguPersetujuan() - kompatibilitas.
     *
     * @return bool
     */
    public function isPendingApproval(): bool
    {
        return $this->adalahMenungguPersetujuan();
    }

    /**
     * Cek apakah dokumen sudah disetujui.
     *
     * @return bool
     */
    public function adalahDisetujui(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Alias untuk adalahDisetujui() - kompatibilitas.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->adalahDisetujui();
    }

    /**
     * Cek apakah dokumen ditolak.
     *
     * @return bool
     */
    public function adalahDitolak(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Alias untuk adalahDitolak() - kompatibilitas.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->adalahDitolak();
    }

    /**
     * Cek apakah dokumen diarsipkan.
     *
     * @return bool
     */
    public function adalahDiarsipkan(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Alias untuk adalahDiarsipkan() - kompatibilitas.
     *
     * @return bool
     */
    public function isArchived(): bool
    {
        return $this->adalahDiarsipkan();
    }

    /**
     * Cek apakah dokumen adalah surat masuk.
     *
     * @return bool
     */
    public function adalahSuratMasuk(): bool
    {
        return $this->type === 'masuk';
    }

    /**
     * Alias untuk adalahSuratMasuk() - kompatibilitas.
     *
     * @return bool
     */
    public function isIncoming(): bool
    {
        return $this->adalahSuratMasuk();
    }

    /**
     * Cek apakah dokumen adalah surat keluar.
     *
     * @return bool
     */
    public function adalahSuratKeluar(): bool
    {
        return $this->type === 'keluar';
    }

    /**
     * Alias untuk adalahSuratKeluar() - kompatibilitas.
     *
     * @return bool
     */
    public function isOutgoing(): bool
    {
        return $this->adalahSuratKeluar();
    }

    /**
     * Cek apakah dokumen adalah rahasia.
     *
     * @return bool
     */
    public function adalahRahasia(): bool
    {
        return $this->classification === 'rahasia';
    }

    /**
     * Alias untuk adalahRahasia() - kompatibilitas.
     *
     * @return bool
     */
    public function isClassified(): bool
    {
        return $this->adalahRahasia();
    }

    /**
     * Cek apakah dokumen adalah telegram.
     *
     * @return bool
     */
    public function adalahTelegram(): bool
    {
        return $this->classification === 'telegram';
    }

    /**
     * Alias untuk adalahTelegram() - kompatibilitas.
     *
     * @return bool
     */
    public function isTelegram(): bool
    {
        return $this->adalahTelegram();
    }

    // ==========================================
    // HELPER METHODS - LABEL
    // ==========================================

    /**
     * Mendapatkan label jenis yang dapat dibaca manusia.
     *
     * @return string
     */
    public function ambilLabelJenis(): string
    {
        return match($this->type) {
            'masuk' => 'Surat Masuk',
            'keluar' => 'Surat Keluar',
            default => ucfirst($this->type),
        };
    }

    /**
     * Alias untuk ambilLabelJenis() - kompatibilitas.
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        return $this->ambilLabelJenis();
    }

    /**
     * Mendapatkan label klasifikasi yang dapat dibaca manusia.
     *
     * @return string
     */
    public function ambilLabelKlasifikasi(): string
    {
        return match($this->classification) {
            'biasa' => 'Biasa',
            'rahasia' => 'Rahasia',
            'telegram' => 'Telegram',
            default => ucfirst($this->classification),
        };
    }

    /**
     * Alias untuk ambilLabelKlasifikasi() - kompatibilitas.
     *
     * @return string
     */
    public function getClassificationLabel(): string
    {
        return $this->ambilLabelKlasifikasi();
    }

    /**
     * Mendapatkan label status yang dapat dibaca manusia.
     *
     * @return string
     */
    public function ambilLabelStatus(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending_approval' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'archived' => 'Diarsipkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Alias untuk ambilLabelStatus() - kompatibilitas.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return $this->ambilLabelStatus();
    }

    /**
     * Mendapatkan label prioritas yang dapat dibaca manusia.
     *
     * @return string
     */
    public function ambilLabelPrioritas(): string
    {
        return match($this->priority) {
            'normal' => 'Normal',
            'high' => 'Tinggi',
            'urgent' => 'Mendesak',
            default => ucfirst($this->priority),
        };
    }

    /**
     * Alias untuk ambilLabelPrioritas() - kompatibilitas.
     *
     * @return string
     */
    public function getPriorityLabel(): string
    {
        return $this->ambilLabelPrioritas();
    }

    // ==========================================
    // HELPER METHODS - BADGE CLASS
    // ==========================================

    /**
     * Mendapatkan kelas CSS badge status.
     *
     * @return string
     */
    public function ambilKelasBadgeStatus(): string
    {
        return match($this->status) {
            'draft' => 'badge bg-secondary',
            'pending_approval' => 'badge bg-warning',
            'approved' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            'archived' => 'badge bg-info',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Alias untuk ambilKelasBadgeStatus() - kompatibilitas.
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return $this->ambilKelasBadgeStatus();
    }

    /**
     * Mendapatkan kelas CSS badge prioritas.
     *
     * @return string
     */
    public function ambilKelasBadgePrioritas(): string
    {
        return match($this->priority) {
            'normal' => 'badge bg-secondary',
            'high' => 'badge bg-warning',
            'urgent' => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
    }

    /**
     * Alias untuk ambilKelasBadgePrioritas() - kompatibilitas.
     *
     * @return string
     */
    public function getPriorityBadgeClass(): string
    {
        return $this->ambilKelasBadgePrioritas();
    }

    // ==========================================
    // ENKRIPSI & DEKRIPSI
    // ==========================================

    /**
     * Enkripsi field sensitif untuk dokumen rahasia.
     *
     * @param  string  $nilai
     * @return string
     */
    public function enkripsiBidang($nilai)
    {
        if ($this->adalahRahasia() && !empty($nilai)) {
            return Crypt::encryptString($nilai);
        }
        return $nilai;
    }

    /**
     * Alias untuk enkripsiBidang() - kompatibilitas.
     *
     * @param  string  $value
     * @return string
     */
    public function encryptField($value)
    {
        return $this->enkripsiBidang($value);
    }

    /**
     * Dekripsi field sensitif untuk dokumen rahasia.
     *
     * @param  string  $nilai
     * @return string
     */
    public function dekripsiBidang($nilai)
    {
        if ($this->adalahRahasia() && !empty($nilai) && $this->is_encrypted) {
            try {
                return Crypt::decryptString($nilai);
            } catch (\Exception $e) {
                return $nilai;
            }
        }
        return $nilai;
    }

    /**
     * Alias untuk dekripsiBidang() - kompatibilitas.
     *
     * @param  string  $value
     * @return string
     */
    public function decryptField($value)
    {
        return $this->dekripsiBidang($value);
    }

    // ==========================================
    // WORKFLOW PERSETUJUAN
    // ==========================================

    /**
     * Mendapatkan role penyetuju berikutnya yang diperlukan untuk dokumen ini.
     * 
     * @return string|null Nama role penyetuju berikutnya (kaur, kasi, pimpinan) atau null jika sudah sepenuhnya disetujui
     */
    public function ambilRolePenyetujuBerikutnya(): ?string
    {
        $levelSaatIni = $this->ambilLevelPersetujuanSaatIni();
        
        return match($levelSaatIni) {
            0 => 'kaur',        // Level 1: Menunggu Kaur
            1 => 'kasi',        // Level 2: Menunggu Kasi (Kaur sudah approve)
            2 => 'pimpinan',    // Level 3: Menunggu Pimpinan (Kasi sudah approve)
            default => null,    // Semua sudah approve
        };
    }

    /**
     * Alias untuk ambilRolePenyetujuBerikutnya() - kompatibilitas.
     * 
     * @return string|null
     */
    public function getNextApproverRole(): ?string
    {
        return $this->ambilRolePenyetujuBerikutnya();
    }

    /**
     * Cek apakah pengguna dapat menyetujui pada level saat ini.
     * 
     * @param User $pengguna
     * @return bool
     */
    public function dapatPenggunaMenyetujui(User $pengguna): bool
    {
        // Dokumen harus menunggu persetujuan
        if (!$this->adalahMenungguPersetujuan()) {
            return false;
        }

        $rolePenyetujuBerikutnya = $this->ambilRolePenyetujuBerikutnya();
        
        if (!$rolePenyetujuBerikutnya) {
            return false; // Sudah sepenuhnya disetujui
        }

        // Cek apakah pengguna memiliki role yang diperlukan
        return $pengguna->hasRole($rolePenyetujuBerikutnya);
    }

    /**
     * Alias untuk dapatPenggunaMenyetujui() - kompatibilitas.
     * 
     * @param User $user
     * @return bool
     */
    public function canUserApprove(User $user): bool
    {
        return $this->dapatPenggunaMenyetujui($user);
    }

    /**
     * Mendapatkan level persetujuan berdasarkan nama role.
     * 
     * @param string $role
     * @return int
     */
    protected function ambilLevelPersetujuanBerdasarkanRole(string $role): int
    {
        return match($role) {
            'kaur' => 1,
            'kasi' => 2,
            'pimpinan' => 3,
            default => 0,
        };
    }

    /**
     * Alias untuk ambilLevelPersetujuanBerdasarkanRole() - kompatibilitas.
     * 
     * @param string $role
     * @return int
     */
    protected function getApprovalLevelByRole(string $role): int
    {
        return $this->ambilLevelPersetujuanBerdasarkanRole($role);
    }

    /**
     * Setujui dokumen oleh pengguna.
     * 
     * @param User $pengguna
     * @param string|null $catatan Catatan persetujuan opsional
     * @return bool
     * @throws \Exception
     */
    public function setujui(User $pengguna, ?string $catatan = null): bool
    {
        if (!$this->dapatPenggunaMenyetujui($pengguna)) {
            throw new \Exception('Anda tidak berwenang menyetujui dokumen ini pada level saat ini.');
        }

        $roleBerikutnya = $this->ambilRolePenyetujuBerikutnya();
        $levelPersetujuan = $this->ambilLevelPersetujuanBerdasarkanRole($roleBerikutnya);

        DB::beginTransaction();
        try {
            // Buat riwayat persetujuan
            $this->riwayatPersetujuan()->create([
                'user_id' => $pengguna->id,
                'action' => 'approved',
                'status' => 'approved',
                'approval_level' => $levelPersetujuan,
                'comment' => $catatan,
                'action_date' => now(),
            ]);

            // Cek apakah ini persetujuan final (Pimpinan)
            if ($roleBerikutnya === 'pimpinan') {
                $this->update([
                    'status' => 'approved',
                    'updated_by' => $pengguna->id,
                ]);
            } else {
                // Masih butuh persetujuan lain, tetap pending
                $this->touch(); // Update timestamp
                $this->update(['updated_by' => $pengguna->id]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Alias untuk setujui() - kompatibilitas.
     * 
     * @param User $user
     * @param string|null $notes
     * @return bool
     * @throws \Exception
     */
    public function approve(User $user, ?string $notes = null): bool
    {
        return $this->setujui($user, $notes);
    }

    /**
     * Tolak dokumen oleh pengguna.
     * 
     * @param User $pengguna
     * @param string $alasan Alasan penolakan
     * @return bool
     * @throws \Exception
     */
    public function tolak(User $pengguna, string $alasan): bool
    {
        if (!$this->dapatPenggunaMenyetujui($pengguna)) {
            throw new \Exception('Anda tidak berwenang menolak dokumen ini pada level saat ini.');
        }

        if (empty($alasan)) {
            throw new \Exception('Alasan penolakan harus diisi.');
        }

        $roleBerikutnya = $this->ambilRolePenyetujuBerikutnya();
        $levelPersetujuan = $this->ambilLevelPersetujuanBerdasarkanRole($roleBerikutnya);

        DB::beginTransaction();
        try {
            // Buat riwayat persetujuan dengan penolakan
            $this->riwayatPersetujuan()->create([
                'user_id' => $pengguna->id,
                'action' => 'rejected',
                'status' => 'rejected',
                'approval_level' => $levelPersetujuan,
                'comment' => $alasan,
                'action_date' => now(),
            ]);

            // Update status dokumen menjadi ditolak
            $this->update([
                'status' => 'rejected',
                'updated_by' => $pengguna->id,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Alias untuk tolak() - kompatibilitas.
     * 
     * @param User $user
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function reject(User $user, string $reason): bool
    {
        return $this->tolak($user, $reason);
    }

    /**
     * Minta koreksi untuk dokumen.
     * 
     * @param User $pengguna
     * @param string $alasan Alasan koreksi
     * @return bool
     * @throws \Exception
     */
    public function mintaKoreksi(User $pengguna, string $alasan): bool
    {
        if (!$this->dapatPenggunaMenyetujui($pengguna)) {
            throw new \Exception('Anda tidak berwenang meminta koreksi dokumen ini.');
        }

        if (empty($alasan)) {
            throw new \Exception('Alasan permintaan koreksi harus diisi.');
        }

        DB::beginTransaction();
        try {
            // Buat permintaan koreksi
            $this->permintaanKoreksi()->create([
                'requested_by' => $pengguna->id,
                'reason' => $alasan,
                'status' => 'pending',
            ]);

            // Update status dokumen
            $this->update([
                'status' => 'correction_requested',
                'updated_by' => $pengguna->id,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Alias untuk mintaKoreksi() - kompatibilitas.
     * 
     * @param User $user
     * @param string $reason
     * @return bool
     * @throws \Exception
     */
    public function requestCorrection(User $user, string $reason): bool
    {
        return $this->mintaKoreksi($user, $reason);
    }
}

