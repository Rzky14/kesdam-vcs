<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * EncryptionService
 * 
 * Handles encryption and decryption of sensitive data, especially for classified documents.
 * Uses Laravel's built-in encryption which uses AES-256-CBC cipher.
 */
class EncryptionService
{
    /**
     * Encrypt sensitive data.
     *
     * @param mixed $data
     * @return string
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encrypt($data): string
    {
        return Crypt::encryptString(is_array($data) ? json_encode($data) : $data);
    }

    /**
     * Decrypt sensitive data.
     *
     * @param string $encrypted
     * @param bool $asArray
     * @return mixed
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
            throw new DecryptException('Unable to decrypt data: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt a file.
     *
     * @param string $filePath
     * @return string Encrypted file contents
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $fileContents = file_get_contents($filePath);
        return $this->encrypt($fileContents);
    }

    /**
     * Decrypt and save file.
     *
     * @param string $encrypted
     * @param string $destinationPath
     * @return bool
     * @throws DecryptException
     */
    public function decryptToFile(string $encrypted, string $destinationPath): bool
    {
        $decrypted = $this->decrypt($encrypted);
        return file_put_contents($destinationPath, $decrypted) !== false;
    }

    /**
     * Check if string is valid JSON.
     *
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Encrypt file and store it.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $filename
     * @return array ['path' => string, 'encrypted_content' => string]
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function encryptAndStore($file, string $directory, ?string $filename = null): array
    {
        $filename = $filename ?? $file->hashName();
        $fileContents = file_get_contents($file->getRealPath());
        $encrypted = $this->encrypt($fileContents);

        // Store encrypted content
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
     * Retrieve and decrypt stored file.
     *
     * @param string $path
     * @return string Decrypted file contents
     * @throws DecryptException
     */
    public function retrieveAndDecrypt(string $path): string
    {
        $fullPath = storage_path("app/{$path}");
        
        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("Encrypted file not found: {$path}");
        }

        $encrypted = file_get_contents($fullPath);
        return $this->decrypt($encrypted);
    }

    /**
     * Alias untuk encryptAndStore - kompatibilitas bahasa Indonesia.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @param string|null $filename
     * @return array
     */
    public function enkripsiDanSimpan($file, string $directory, ?string $filename = null): array
    {
        return $this->encryptAndStore($file, $directory, $filename);
    }

    /**
     * Alias untuk retrieveAndDecrypt - kompatibilitas bahasa Indonesia.
     *
     * @param string $path
     * @return string
     */
    public function ambilDanDekripsi(string $path): string
    {
        return $this->retrieveAndDecrypt($path);
    }
}
