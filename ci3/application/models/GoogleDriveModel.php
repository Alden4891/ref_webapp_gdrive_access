<?php

/*
Note: please enable Google Drive API first
*/

defined('BASEPATH') OR exit('No direct script access allowed');
require_once FCPATH.'vendor/autoload.php';

class GoogleDriveModel extends CI_Model {

    private $client;
    private $driveService;
    private $tokenPath;

    public function __construct() {
        parent::__construct();
        //alden.roxy@gmail.com->console->my-google-drive-pyapp->ci3-gdrive-app -> 
        $this->tokenPath = FCPATH."assets/auth/credentials/gdrive_token.json"; // Path to store token
        $this->client = new Google_Client();
        $this->client->setAuthConfig(FCPATH."assets/auth/client_secret_823425529917-la64cdoa1fcj8vtq6glu1lbtur5ng33i.apps.googleusercontent.com.json");
        $this->client->setApplicationName('Gdrive API PHP');
        // $this->client->addScope(Google_Service_Drive::DRIVE);
        $this->client->setScopes([
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/drive'
        ]);


        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        // Load the token if it exists
        $this->loadToken();

        // Initialize the Google Drive service
        $this->driveService = new Google_Service_Drive($this->client);
    }

    private function loadToken() {
        if (file_exists($this->tokenPath)) {
            $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $this->client->setAccessToken($accessToken);

            // Refresh the token if it has expired
            if ($this->client->isAccessTokenExpired()) {
                $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($this->tokenPath, json_encode($this->client->getAccessToken()));
            }
        }
    }

    public function saveToken($code) {
        try {

            $accessToken = $this->client->fetchAccessTokenWithAuthCode(trim($code));

            if (isset($accessToken['error'])) {
                throw new Exception('Error fetching access token: ' . $accessToken['error']);
            }

            $this->client->setAccessToken($accessToken);

            // Save the token to a file
            if (!file_exists(dirname($this->tokenPath))) {
                mkdir(dirname($this->tokenPath), 0700, true);
            }
            file_put_contents($this->tokenPath, json_encode($accessToken));
        } catch (Exception $e) {
            log_message('error', 'Error saving access token: ' . $e->getMessage());
            throw $e;
        }
    }


    public function getAuthUrl() {
        return $this->client->createAuthUrl();
    }


    //----------------------------------------------------------------

    public function listFiles($size) {
        $response = $this->driveService->files->listFiles(array(
            'pageSize' => $size,
            'fields' => 'nextPageToken, files(id, name)'
        ));

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

    public function uploadFile($filename, $filepath, $mimetype) {
        //1k7-fTT-Q8xgJcHGFMkNJlYUIFbpJG9bF
        $folderId = '16lEMWn2r2csC4yTqKxdyXqjuW4RSDaed'; 
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => $filename,
                'parents' => array($folderId)
            ));
            $content = file_get_contents($filepath);
            $file = $this->driveService->files->create($fileMetadata, array(
                'data' => $content,
                'mimeType' => $mimetype,
                'uploadType' => 'multipart',
                'fields' => 'id'
            ));
            // if ($this->moveFile($file->id)) {
            print('File ID: ' . $file->id . ' - successfully uploaded!');
            return $file->id;
            // }
        } catch (Exception $e) {
            print('Error: ' . $e->getMessage());
        }
    }


    public function downloadFile($file_id, $filepath) {
        try {
            $response = $this->driveService->files->get($file_id, array('alt' => 'media'));
            $content = $response->getBody()->getContents();
            file_put_contents($filepath, $content);
            return 'Download successful.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function createFolder($name) {
        try {
            $fileMetadata = new Google_Service_Drive_DriveFile(array(
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder'
            ));
            $file = $this->driveService->files->create($fileMetadata, array(
                'fields' => 'id'
            ));
            return 'Folder ID: ' . $file->id;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function searchFile($size, $query) {
        try {
            $response = $this->driveService->files->listFiles(array(
                'pageSize' => $size,
                'fields' => 'nextPageToken, files(id, name, kind, mimeType)',
                'q' => $query
            ));
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

    public function getItemId($filename) {
        try {
            $response = $this->driveService->files->listFiles(array(
                'pageSize' => 1,
                'fields' => 'nextPageToken, files(name, id)',
                'q' => "name = '{$filename}'"
            ));
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

    public function moveFile($file_id) {
        try {
            $folder_id = $this->getItemId('uploaded_images');
            if (!$folder_id) {
                return 'Folder not found.';
            }
            $file = $this->driveService->files->get($file_id, array('fields' => 'parents'));
            $previous_parents = join(',', $file->getParents());

            $this->driveService->files->update($file_id, new Google_Service_Drive_DriveFile(), array(
                'addParents' => $folder_id,
                'removeParents' => $previous_parents,
                'fields' => 'id, parents'
            ));
            return 'File moved successfully.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
}
