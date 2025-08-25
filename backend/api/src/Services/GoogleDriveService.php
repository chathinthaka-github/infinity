<?php
namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Psr\Http\Message\UploadedFileInterface;

class GoogleDriveService
{
    private $client;
    private $drive;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
        $this->client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
        $this->client->setRedirectUri('http://localhost');
        $this->client->setScopes([Drive::DRIVE]);
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');

        // Set refresh token
        $this->client->refreshToken($_ENV['GOOGLE_REFRESH_TOKEN']);

        $this->drive = new Drive($this->client);
    }

    public function uploadFile(UploadedFileInterface $file, array $metadata = [])
    {
        try {
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $metadata['name'] ?? $file->getClientFilename(),
                'parents' => [$_ENV['GOOGLE_FOLDER_ID']]
            ]);

            $content = $file->getStream()->getContents();

            $driveFile = $this->drive->files->create(
                $fileMetadata,
                [
                    'data' => $content,
                    'mimeType' => $file->getClientMediaType(),
                    'uploadType' => 'multipart'
                ]
            );

            // Make file publicly viewable
            $permission = new \Google\Service\Drive\Permission();
            $permission->setRole('reader');
            $permission->setType('anyone');
            $this->drive->permissions->create($driveFile->getId(), $permission);

            return [
                'success' => true,
                'file_id' => $driveFile->getId(),
                'view_url' => 'https://drive.google.com/file/d/' . $driveFile->getId() . '/view',
                'download_url' => 'https://drive.google.com/uc?export=download&id=' . $driveFile->getId()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Upload failed: ' . $e->getMessage()
            ];
        }
    }

    public function getViewUrl($fileId)
    {
        return 'https://drive.google.com/file/d/' . $fileId . '/preview';
    }

    public function getDownloadUrl($fileId)
    {
        return 'https://drive.google.com/uc?export=download&id=' . $fileId;
    }

    public function deleteFile($fileId)
    {
        try {
            $this->drive->files->delete($fileId);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
