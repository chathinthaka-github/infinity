<?php
declare(strict_types=1);

namespace App\Services;

use Psr\Http\Message\UploadedFileInterface;

/**
 * LocalStorageService
 * Stores uploaded files on the local filesystem (development fallback).
 */
class LocalStorageService
{
    private string $uploadPath;
    private string $baseUrl;
    private string $publicPath;

    public function __construct(string $uploadPath = null)
    {
        $this->uploadPath = $uploadPath ?? ($_ENV['UPLOAD_PATH'] ?? __DIR__ . '/../../storage/uploads');
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0775, true);
        }
        $this->baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/');
        $this->publicPath = rtrim($_ENV['UPLOAD_PUBLIC_PATH'] ?? '/uploads', '/');
    }

    /**
     * Move the UploadedFileInterface to the uploads folder and return metadata array.
     */
    public function storeFromUploadedFile(UploadedFileInterface $file, string $clientFilename): array
    {
        $ext = pathinfo($clientFilename, PATHINFO_EXTENSION);
        $safe = bin2hex(random_bytes(12)) . ($ext ? '.' . $ext : '');
        $target = $this->uploadPath . DIRECTORY_SEPARATOR . $safe;

        // PSR-7 moveTo writes to filesystem
        $file->moveTo($target);

        $url = $this->baseUrl . $this->publicPath . '/' . $safe;

        return [
            'storage' => 'local',
            'id' => $safe,
            'url' => $url,
            'filename' => $clientFilename,
            'mime' => $file->getClientMediaType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?? 0,
        ];
    }
}
