<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Model Lampiran
 * 
 * Merepresentasikan lampiran file yang terlampir pada surat/dokumen.
 * Sesuai dengan UML Class Diagram.
 * 
 * @property int $id ID Lampiran
 * @property int $surat_id ID Dokumen terkait
 * @property string $nama_file Nama file asli
 * @property string $tipe_file Tipe/ekstensi file
 * @property int $ukuran_file Ukuran file dalam bytes
 * @property string $path_file Path penyimpanan file
 * @property \Carbon\Carbon $tanggal_unggah Tanggal file diunggah
 * @property bool $is_encrypted Apakah file terenkripsi
 */
class Lampiran extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nama tabel yang digunakan oleh model.
     *
     * @var string
     */
    protected $table = 'lampiran';

    /**
     * Primary key untuk model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Atribut yang dapat diisi secara massal.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dokumen_id',
        'nama_file',
        'tipe_file',
        'ukuran_file',
        'path_file',
        'tanggal_unggah',
        'is_encrypted',
        'encryption_key_id',
        'diunggah_oleh',
        'hash_file',
        'keterangan',
        'is_active',
    ];

    /**
     * Atribut yang harus di-cast ke tipe tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_unggah' => 'datetime',
        'ukuran_file' => 'integer',
        'is_encrypted' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==========================================
    // RELASI (RELATIONSHIPS)
    // ==========================================

    /**
     * Mendapatkan dokumen yang memiliki lampiran ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dokumen()
    {
        return $this->belongsTo(Surat::class, 'dokumen_id');
    }

    /**
     * Mendapatkan pengguna yang mengunggah lampiran ini.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    // ==========================================
    // METHODS SESUAI UML CLASS DIAGRAM
    // ==========================================

    /**
     * Mengunggah file lampiran baru.
     * Sesuai UML: +unggah(): void
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $suratId ID dokumen yang dilampiri
     * @param string $direktori Direktori penyimpanan
     * @return static
     */
    public static function unggah($file, int $suratId, string $direktori = 'lampiran'): self
    {
        $path = $file->store($direktori);
        
        return static::create([
            'dokumen_id' => $suratId,
            'nama_file' => $file->getClientOriginalName(),
            'tipe_file' => $file->getClientOriginalExtension(),
            'ukuran_file' => $file->getSize(),
            'path_file' => $path,
            'tanggal_unggah' => now(),
            'diunggah_oleh' => Auth::id(),
            'is_encrypted' => false,
        ]);
    }

    /**
     * Mengunduh file lampiran.
     * Sesuai UML: +unduh(): void
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function unduh()
    {
        if (!Storage::exists($this->path_file)) {
            throw new \Exception("File tidak ditemukan: {$this->path_file}");
        }

        return Storage::download($this->path_file, $this->nama_file);
    }

    /**
     * Menghapus file lampiran.
     * Sesuai UML: +hapus(): void
     *
     * @return bool
     */
    public function hapus(): bool
    {
        // Hapus file dari storage
        if (Storage::exists($this->path_file)) {
            Storage::delete($this->path_file);
        }

        // Hapus record dari database (soft delete)
        return $this->delete();
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope untuk mendapatkan lampiran berdasarkan tipe file.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $tipe
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBerdasarkanTipe($query, string $tipe)
    {
        return $query->where('tipe_file', $tipe);
    }

    /**
     * Scope untuk mendapatkan lampiran yang terenkripsi.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTerenkripsi($query)
    {
        return $query->where('is_encrypted', true);
    }

    /**
     * Scope untuk mendapatkan lampiran milik dokumen tertentu.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $suratId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUntukDokumen($query, int $suratId)
    {
        return $query->where('dokumen_id', $suratId);
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Mendapatkan ukuran file dalam format yang mudah dibaca.
     *
     * @return string
     */
    public function getUkuranFileFormatted(): string
    {
        $bytes = $this->ukuran_file;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Mengecek apakah file masih ada di storage.
     *
     * @return bool
     */
    public function fileAda(): bool
    {
        return Storage::exists($this->path_file);
    }

    /**
     * Mendapatkan URL untuk mengakses file.
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        if ($this->fileAda()) {
            return Storage::url($this->path_file);
        }
        return null;
    }
}



