<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleDriveController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('google-drive', [GoogleDriveController::class, 'index']);
Route::post('google-drive/upload', [GoogleDriveController::class, 'upload'])->name('google.drive.upload');
Route::get('google-drive/authenticate', [GoogleDriveController::class, 'authenticate']);
Route::get('google-drive/success', [GoogleDriveController::class, 'success']);
