<?php
namespace App\Services;

# composer require google/apiclient
#path: app/Services
#name: GoogleDriveService.php


use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Exception;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private $client;
    private $driveService;
    private $tokenPath;

    public function __construct()
    {
        $this->tokenPath = storage_path('app/google-drive/aaquinones.fo12.dswd.gov.ph.access.token.json');
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/google-drive/aaquinones.fo12.dswd.gov.ph.client_secret.json'));
        $this->client->setApplicationName('Gdrive API PHP');
        $this->client->setScopes([
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/drive'
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        $this->loadToken();

        $this->driveService = new Google_Service_Drive($this->client);
    }

    private function loadToken()
    {
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);

            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
            }
        }
    }

    public function saveToken($code)
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode(trim($code));

            if (isset($accessToken['error'])) {
                throw new Exception('Error fetching access token: ' . $accessToken['error']);
            }

            $this->client->setAccessToken($accessToken);

            if (!file_exists(dirname($this->tokenPath))) {
                mkdir(dirname($this->tokenPath), 0700, true);
            }
            file_put_contents($this->tokenPath, json_encode($accessToken));
        } catch (Exception $e) {
            logger()->error('Error saving access token: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function listFiles($size)
    {
        $response = $this->driveService->files->listFiles([
            'pageSize' => $size,
            'fields' => 'nextPageToken, files(id, name)'
        ]);

        $files = $response->getFiles();
        if (empty($files)) {
            return 'No files found.';
        } else {
            $result = 'Files:';
            foreach ($files as $file) {
                $result .= sprintf("%s (%s)\n", $file->getName(), $file->getId());
            }
            return $result;
        }
    }

    public function uploadFile($filename, $filepath, $mimetype,$folderId )
    {   
        $folderId = '1rwBxYJfJHPgz0dZqIl2QLbokSZcgOgq4';
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $filename,
                'parents' => [$folderId]
            ]);
            $content = file_get_contents($filepath);
            $file = $this->driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimetype,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);
            
            return $file->id;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    public function uploadFileToSharedDrive($filename, $filepath, $mimetype,$destination_folder)
    {   
        //sample 123
        $folderId = '1rwBxYJfJHPgz0dZqIl2QLbokSZcgOgq4';
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $filename,
                'parents' => [$folderId]
            ]);
            $content = file_get_contents($filepath);
            $file = $this->driveService->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimetype,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);
            $this->moveFile($file->id,$destination_folder);
            return $file->id;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function downloadFile($file_id, $filepath)
    {
        try {
            $response = $this->driveService->files->get($file_id, ['alt' => 'media']);
            $content = $response->getBody()->getContents();
            file_put_contents($filepath, $content);
            return 'Download successful.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function createFolder($name)
    {
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            $file = $this->driveService->files->create($fileMetadata, [
                'fields' => 'id'
            ]);
            return 'Folder ID: ' . $file->id;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function searchFile($size, $query)
    {
        try {
            $response = $this->driveService->files->listFiles([
                'pageSize' => $size,
                'fields' => 'nextPageToken, files(id, name, kind, mimeType)',
                'q' => $query
            ]);
            $files = $response->getFiles();
            if (empty($files)) {
                return 'No files found.';
            } else {
                $result = 'Files:';
                foreach ($files as $file) {
                    $result .= sprintf("%s (%s)\n", $file->getName(), $file->getId());
                }
                return $result;
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function getItemId($filename)
    {
        try {
            $response = $this->driveService->files->listFiles([
                'pageSize' => 1,
                'fields' => 'nextPageToken, files(name, id)',
                'q' => "name = '{$filename}'"
            ]);
            $files = $response->getFiles();
            if (empty($files)) {
                return null;
            } else {
                return $files[0]->getId();
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function moveFile($file_id, $folder_id)
    {
        try {
            // Ensure the folder ID is provided
            if (!$folder_id) {
                return 'Folder not found.';
            }

            // Retrieve the current parents of the file
            $file = $this->driveService->files->get($file_id, [
                'fields' => 'parents',
                'supportsAllDrives' => true
            ]);
            $previous_parents = join(',', $file->getParents());

            // Update the file to add it to the new folder and remove it from the previous folders
            $this->driveService->files->update($file_id, new Google_Service_Drive_DriveFile(), [
                'addParents' => $folder_id,
                'removeParents' => $previous_parents,
                'fields' => 'id, parents',
                'supportsAllDrives' => true
            ]);

            return 'File moved successfully.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function listFilesInDrive($driveId)
    {
        try {
            $response = $this->driveService->files->listFiles([
                'q' => "'{$driveId}' in parents",
                'pageSize' => 10,
                'fields' => 'nextPageToken, files(id, name)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ]);

            $files = $response->getFiles();
            if (empty($files)) {
                return 'No files found.';
            } else {
                $result = 'Files:';
                foreach ($files as $file) {
                    $result .= sprintf("%s (%s)\n", $file->getName(), $file->getId());
                }
                return $result;
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }


}

