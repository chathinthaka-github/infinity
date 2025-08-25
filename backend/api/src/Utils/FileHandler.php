<?php
declare(strict_types=1);

namespace App\Utils;

use Psr\Http\Message\UploadedFileInterface;
use App\Services\LocalStorageService;
use App\Services\GoogleDriveService;

/**
 * FileHandler
 * Validate uploaded files and store them either to Google Drive (if enabled and available)
 * or to local storage (LocalStorageService).
 */
class FileHandler
{
    private array $allowedExts;
    private int $maxSize;
    private LocalStorageService $local;
    private bool $useGoogleDrive;
    private ?GoogleDriveService $gdrive = null;

    public function __construct(?\Psr\Container\ContainerInterface $container = null)
    {
        $this->allowedExts = array_map('trim', explode(',', ($_ENV['ALLOWED_FILE_TYPES'] ?? 'pdf,doc,docx,mp4,mp3,wav,jpg,png')));
        $this->maxSize = (int)($_ENV['MAX_FILE_SIZE'] ?? 52428800);
        $uploadPath = $_ENV['UPLOAD_PATH'] ?? __DIR__ . '/../../storage/uploads';
        $this->local = new LocalStorageService($uploadPath);

        $this->useGoogleDrive = (strtolower($_ENV['USE_GOOGLE_DRIVE'] ?? 'false') === 'true');

        if ($this->useGoogleDrive) {
            // prefer DI container googleDrive if available
            if ($container && $container->has('googleDrive')) {
                $this->gdrive = $container->get('googleDrive');
            } elseif (class_exists(GoogleDriveService::class)) {
                try {
                    $this->gdrive = new GoogleDriveService();
                } catch (\Throwable $e) {
                    // keep null and fallback to local
                    $this->gdrive = null;
                }
            }
        }
    }

    /**
     * Handle an uploaded file.
     * Returns metadata:
     * [
     *   'storage' => 'google'|'local',
     *   'id' => drive id or local filename,
     *   'url' => public url,
     *   'filename' => original filename,
     *   'mime' => mime type,
     *   'size' => int
     * ]
     *
     * Throws InvalidArgumentException on validation errors.
     */
    public function handleUpload(UploadedFileInterface $file): array
    {
        $size = $file->getSize() ?? 0;
        if ($size <= 0) {
            throw new \InvalidArgumentException('Empty file uploaded');
        }
        if ($size > $this->maxSize) {
            throw new \InvalidArgumentException('File too large (max ' . $this->maxSize . ' bytes)');
        }

        $clientFilename = $file->getClientFilename() ?? 'upload.bin';
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExts, true)) {
            throw new \InvalidArgumentException('File extension not allowed: ' . $ext);
        }
        $mime = $file->getClientMediaType() ?? 'application/octet-stream';

        // Try Google Drive if enabled and service available
        if ($this->useGoogleDrive && $this->gdrive !== null) {
            // move to temp file and upload
            $tmp = tempnam(sys_get_temp_dir(), 'upload_');
            $file->moveTo($tmp);
            try {
                $result = $this->gdrive->uploadFile($tmp, $clientFilename, $mime);
                // expected ['id' => '...', 'url' => '...']
                if (is_array($result) && !empty($result['id'])) {
                    @unlink($tmp);
                    return [
                        'storage' => 'google',
                        'id' => $result['id'],
                        'url' => $result['url'] ?? (method_exists($this->gdrive, 'getDownloadUrl') ? $this->gdrive->getDownloadUrl($result['id']) : null),
                        'filename' => $clientFilename,
                        'mime' => $mime,
                        'size' => $size,
                    ];
                }
            } catch (\Throwable $e) {
                // fallback to local storage if drive fails
                @unlink($tmp);
            }
        }

        // fallback to local
        return $this->local->storeFromUploadedFile($file, $clientFilename);
    }
}
