<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class GoogleDriveController extends Controller
{
    protected $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        // dd($request->file('imageFile'));
        // $request->validate([
        //     'imageFile.*' => 'required|mimes:gif,jpg,png|max:2048',
        // ]);

        $files = $request->file('imageFile');
        $upload_data = [];
        
        // upload($files);
        foreach ($files as $file) {
            $path = $file->store('uploads', 'public');
            // dd($path);
            $filename = basename($path);
            $filepath = storage_path('app/public/' . $path);
            $mimetype = $file->getMimeType();

            $data = $this->driveService->uploadFileToSharedDrive($filename, $filepath, $mimetype,"14gHgU7_81Csdy9YLKHQsxkzy-0KUs7hj"); //laradrive/uploads
            
            $upload_data[] = ['filename' => $filename, 'link' => "https://drive.google.com/file/d/{$data}/view"];

            // Optionally delete the local file after upload
            // Storage::delete('app/public/' . $path);
            unlink($filepath);
        }

        return view('upload', compact('upload_data'));
    }

    public function authenticate(Request $request)
    {
        if ($request->has('code')) {
            $this->driveService->saveToken($request->input('code'));
            return redirect()->route('google.drive.success');
        } else {
            $authUrl = $this->driveService->getAuthUrl();
            return redirect()->away($authUrl);
        }
    }

    public function success()
    {
        return "Authentication successful. You can now use the Google Drive API.";
    }
}
