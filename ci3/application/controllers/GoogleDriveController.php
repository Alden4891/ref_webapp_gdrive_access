<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GoogleDriveController extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('GoogleDriveModel');
        $this->load->helper(array('form', 'url'));

        
    }

    public function index(){

        $this->load->view('upload.php');
    }
    
    public function upload() {
        $config['upload_path'] = './uploads/';
        $config['allowed_types'] = 'gif|jpg|png';

        $this->load->library('upload', $config);

        // Count total files
        $file_count = count($_FILES['imageFile']['name']);

        // Array to store upload data
        $upload_data = array();

        // Handle file uploads
        for ($i = 0; $i < $file_count; $i++) {
            $_FILES['userfile']['name']     = $_FILES['imageFile']['name'][$i];
            $_FILES['userfile']['type']     = $_FILES['imageFile']['type'][$i];
            $_FILES['userfile']['tmp_name'] = $_FILES['imageFile']['tmp_name'][$i];
            $_FILES['userfile']['error']    = $_FILES['imageFile']['error'][$i];
            $_FILES['userfile']['size']     = $_FILES['imageFile']['size'][$i];

            // Initialize upload library for each file
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('userfile')) {
                $error = array('error' => $this->upload->display_errors());
                $this->load->view('upload.php', $error);
            } else {
                $upload_data[] = $this->upload->data(); // Store upload data for each file
            }
        }

        // Now $upload_data contains information about each uploaded file
        foreach ($upload_data as $file) {
            $filename = $file['file_name'];
            $filepath = FCPATH . 'uploads/' . $filename;
            $mimetype = 'image/jpeg'; // Example mimetype, adjust according to your needs

            // Assuming GoogleDriveModel is your model for interacting with Google Drive
            $data = $this->GoogleDriveModel->uploadFile($filename, $filepath, $mimetype);

            // Output link to the uploaded file on Google Drive
            echo "<a href='https://drive.google.com/file/d/{$data}/view'>{$filename}</a><br>";

            // Optionally, delete the local file after upload
            unlink($filepath);
        }

        // Optionally, load a success view or redirect
        // $this->load->view('upload_success', array('upload_data' => $upload_data));
    }


    public function authenticate() {

        if ($this->input->get('code')) {
            // Handle the OAuth 2.0 server response.
            $this->GoogleDriveModel->saveToken($this->input->get('code'));
            // redirect('GoogleDriveController/success');
        } else {
            // Redirect to Google's OAuth 2.0 server.
            $authUrl = $this->GoogleDriveModel->getAuthUrl();
            redirect($authUrl);
        }

        // print(1);
    }

    public function success() {
        echo "Authentication successful. You can now use the Google Drive API.";
    }


    // Other methods to list files, download files, etc...
}
