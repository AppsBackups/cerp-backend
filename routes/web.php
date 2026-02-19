<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::get('/property-image/{filename}', function ($filename) {
    $filename = basename($filename); // prevent traversal
    $path = storage_path('app/public/property_images/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path);
});

Route::get('/phpinfo', function () {
    return phpinfo();
});


Route::get('/test-image', function () {
    $path = storage_path('app/public/property_images/05tdaNRIDrl55KnwugP2raNSXHCkhAJ5iZQE5wNF.jpg');

    if (!file_exists($path)) {
        return '❌ File does not exist';
    }

    return response()->file($path);
});
