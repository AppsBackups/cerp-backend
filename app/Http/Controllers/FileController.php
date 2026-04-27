<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048', // max file size 2MB
        ]);

        $path = $request->file('file')->store('uploads');

        return response()->json(['message' => 'File uploaded successfully!', 'path' => $path]);
    }
}
