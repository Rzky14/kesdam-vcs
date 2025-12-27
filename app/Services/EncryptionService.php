<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * LayananEnkripsi (EncryptionService)
 * 
 * Menangani enkripsi dan dekripsi data sensitif, terutama untuk dokumen rahasia.
 * Menggunakan enkripsi bawaan Laravel yang menggunakan cipher AES-256-CBC.
 */
class EncryptionService
{
    /**
     * Enkripsi data sensitif.
     *
     * @param mixed $data Data yang akan dienkripsi
     * @return string Data terenkripsi
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encrypt($data): string
    {
        return Crypt::encryptString(is_array($data) ? json_encode($data) : $data);
    }

    /**
     * Dekripsi data sensitif.
     *
     * @param string $encrypted Data terenkripsi
     * @param bool $asArray Kembalikan sebagai array jika true
     * @return mixed Data yang sudah didekripsi
     * @throws DecryptException
     */
    public function decrypt(string $encrypted, bool $asArray = false)
    {
        try {
            $decrypted = Crypt::decryptString($encrypted);
            
            if ($asArray && $this->isJson($decrypted)) {
                return json_decode($decrypted, true);
            }
            
            return $decrypted;
        } catch (DecryptException $e) {
            throw new DecryptException('Gagal mendekripsi data: ' . $e->getMessage());
        }
    }

    /**
     * Enkripsi file.
     *
     * @param string $filePath Path file yang akan dienkripsi
     * @return string Konten file terenkripsi
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File tidak ditemukan: {$filePath}");
        }

        $fileContents = file_get_contents($filePath);
        return $this->encrypt($fileContents);
    }

    /**
     * Dekripsi dan simpan file.
     *
     * @param string $encrypted Data terenkripsi
     * @param string $destinationPath Path tujuan penyimpanan
     * @return bool Status berhasil atau gagal
     * @throws DecryptException
     */
    public function decryptToFile(string $encrypted, string $destinationPath): bool
    {
        $decrypted = $this->decrypt($encrypted);
        return file_put_contents($destinationPath, $decrypted) !== false;
    }

    /**
     * Periksa apakah string adalah JSON yang valid.
     *
     * @param string $string String yang akan diperiksa
     * @return bool True jika JSON valid
     */
    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Enkripsi file dan simpan.
     *
     * @param \Illuminate\Http\UploadedFile $file File yang diupload
     * @param string $directory Direktori penyimpanan
     * @param string|null $filename Nama file (opsional)
     * @return array ['path' => string, 'encrypted_content' => string]
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptAndStore($file, string $directory, ?string $filename = null): array
    {
        $filename = $filename ?? $file->hashName();
        $fileContents = file_get_contents($file->getRealPath());
        $encrypted = $this->encrypt($fileContents);

        // Simpan konten terenkripsi
        $path = storage_path("app/{$directory}/{$filename}.encrypted");
        
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $encrypted);

        return [
            'path' => "{$directory}/{$filename}.encrypted",
            'encrypted_content' => $encrypted,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    /**
     * Ambil dan dekripsi file yang tersimpan.
     *
     * @param string $path Path file terenkripsi
     * @return string Konten file yang sudah didekripsi
     * @throws DecryptException
     */
    public function retrieveAndDecrypt(string $path): string
    {
        $fullPath = storage_path("app/{$path}");
        
        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("File terenkripsi tidak ditemukan: {$path}");
        }

        $encrypted = file_get_contents($fullPath);
        return $this->decrypt($encrypted);
    }

    /**
     * Alias untuk encryptAndStore - enkripsi dan simpan file.
     *
     * @param \Illuminate\Http\UploadedFile $file File yang diupload
     * @param string $directory Direktori penyimpanan
     * @param string|null $filename Nama file (opsional)
     * @return array Informasi file terenkripsi
     */
    public function enkripsiDanSimpan($file, string $directory, ?string $filename = null): array
    {
        return $this->encryptAndStore($file, $directory, $filename);
    }

    /**
     * Alias untuk retrieveAndDecrypt - ambil dan dekripsi file.
     *
     * @param string $path Path file terenkripsi
     * @return string Konten file yang sudah didekripsi
     */
    public function ambilDanDekripsi(string $path): string
    {
        return $this->retrieveAndDecrypt($path);
    }
}



