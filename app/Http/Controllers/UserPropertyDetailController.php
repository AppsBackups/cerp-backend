<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MobileUser;
use App\Models\UserPropertyDetail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserPropertyDetailController extends Controller
{


public function stores(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'circle' => 'required|string',
        'pin' => 'required|string',
        'info' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'floors_num' => 'required|integer',
        'basement' => 'required|integer',
        'land_area' => 'required|string',
        'covered_area' => 'required|string',
        'land' => 'nullable|string',
        'other' => 'nullable|string',
        'comments' => 'nullable|string',
        'picture' => 'nullable|image',
        'capture_time' => 'string',
        'submission_time' => 'required|string'
    ]);

    try {
        // Find user
        $user = MobileUser::where('username', $request->username)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Handle local picture upload and convert to base64
        $picturePath = null;
        $base64Picture = null;

        if ($request->hasFile('picture')) {
            $picture = $request->file('picture');
            $picturePath = $picture->store('property_images', 'public');
            $base64Picture = base64_encode(file_get_contents($picture->getRealPath()));
        }

        // Save locally
        $detail = new UserPropertyDetail([
            'user_id' => $user->id,
            'username' => $request->username,
            'circle' => $request->circle,
            'pin' => $request->pin,
            'info' => $request->info,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'floors_num' => $request->floors_num,
            'basement' => $request->basement,
            'land_area' => $request->land_area,
            'covered_area' => $request->covered_area,
            'land' => $request->land,
            'other' => $request->other,
            'comments' => $request->comments,
            'picture_path' => $picturePath,
            'capture_time' => $request->capture_time,
            'submission_time' => $request->submission_time,
        ]);

        $detail->save();

        // Update status in related table
        \App\Models\UserCirclePin::where('pin', $request->pin)
            ->update(['status' => true]);

        // Prepare payload for external API
        $payload = [
            'Username' => $request->username,
            'Circle' => $request->circle,
            'Pin' => $request->pin,
            'Info' => $request->info,
            'Latitude' => $request->latitude,
            'Longitude' => $request->longitude,
            'FloorsNum' => $request->floors_num,
            'Basement' => $request->basement,
            'LandArea' => $request->land_area,
            'CoveredArea' => $request->covered_area,
            'Land' => $request->land ?? '',
            'Other' => $request->other ?? '',
            'Comments' => $request->comments ?? '',
            'CaptureTime' => $request->capture_time,
            'SubmissionTime' => $request->submission_time,
            'Picture' => $base64Picture // null if not uploaded
        ];

        // Send JSON to external API
        $response = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(config('services.cerp.url'), $payload);

        // Handle response
        if ($response->successful()) {
            return response()->json([
                'message' => 'Property detail submitted successfully',
                'data' => $detail,
                'external_response' => $response->json()
            ], 201);
        }

        return response()->json([
            'message' => 'Property detail saved locally, but external API failed',
            'data' => $detail,
            'external_status' => $response->status(),
            'external_response' => $response->body()
        ], 202);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred',
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTrace() : null
        ], 500);
    }
}





// public function store(Request $request)
// {
//     $request->validate([
//         'username' => 'required|string',
//         'circle' => 'required|string',
//         'pin' => 'required|string',
//         'info' => 'required|string',
//         'latitude' => 'required|numeric',
//         'longitude' => 'required|numeric',
//         'floors_num' => 'required|integer',
//         'basement' => 'required|integer',
//         'land_area' => 'required|string',
//         'covered_area' => 'required|string',
//         'land' => 'nullable|string',
//         'other' => 'nullable|string',
//         'comments' => 'nullable|string',
//         'picture' => 'nullable|image',
//         'capture_time' => 'required|string',
//         'submission_time' => 'required|string',
//         'Store_front' => 'nullable|integer'
//     ]);

//     $user = \App\Models\MobileUser::where('username', $request->username)->first();
//     if (!$user) {
//         return response()->json(['error' => 'User not found'], 404);
//     }

//     $picturePath = null;
//     if ($request->hasFile('picture')) {
//         $picturePath = $request->file('picture')->store('property_images', 'public');
//     }

//     // Check if a record with the same pin already exists
//     $existingDetail = \App\Models\UserPropertyDetail::where('pin', $request->pin)->first();

//     if ($existingDetail) {
//         // Update existing record
//         $existingDetail->update([
//             'user_id' => $user->id,
//             'username' => $request->username,
//             'circle' => $request->circle,
//             'info' => $request->info,
//             'latitude' => $request->latitude,
//             'longitude' => $request->longitude,
//             'floors_num' => $request->floors_num,
//             'basement' => $request->basement,
//             'land_area' => $request->land_area,
//             'covered_area' => $request->covered_area,
//             'land' => $request->land,
//             'other' => $request->other,
//             'comments' => $request->comments,
//             'capture_time' => $request->capture_time,
//             'submission_time' => $request->submission_time,
//             'resubmission' => 1, // 👈 set when updating
//             'Store_front' => $request->input('Store_front', 0),
//         ]);

//         // Only update picture if a new one is uploaded
//         if ($picturePath) {
//             $existingDetail->picture_path = $picturePath;
//             $existingDetail->save();
//         }

//         $message = 'Property detail updated successfully';
//     } else {
//         // Create new record
//         $detail = new \App\Models\UserPropertyDetail([
//             'user_id' => $user->id,
//             'username' => $request->username,
//             'circle' => $request->circle,
//             'pin' => $request->pin,
//             'info' => $request->info,
//             'latitude' => $request->latitude,
//             'longitude' => $request->longitude,
//             'floors_num' => $request->floors_num,
//             'basement' => $request->basement,
//             'land_area' => $request->land_area,
//             'covered_area' => $request->covered_area,
//             'land' => $request->land,
//             'other' => $request->other,
//             'comments' => $request->comments,
//             'picture_path' => $picturePath,
//             'capture_time' => $request->capture_time,
//             'submission_time' => $request->submission_time,
//             'resubmission' => 0, // 👈 set when updating
//             'Store_front' => $request->input('Store_front', 0),
//         ]);

//         $detail->save();

//         $message = 'Property detail submitted successfully';
//     }

//     // Update pin status in UserCirclePin table
//     \App\Models\UserCirclePin::where('pin', $request->pin)
//         ->update(['status' => true]);

//     return response()->json(['message' => $message], 201);
// }




// public function store(Request $request)
// {
//     try {
//         // Before validation, check file upload errors

//         if ($request->has('picture') && !$request->hasFile('picture')) {
//     $uploadError = $_FILES['picture']['error'] ?? null;
//     $errorMessages = [
//         UPLOAD_ERR_INI_SIZE => 'File exceeds PHP upload_max_filesize',
//         UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
//         UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
//         UPLOAD_ERR_NO_FILE => 'No file was uploaded',
//         UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory available',
//         UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
//         UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
//     ];

//     Log::error('File upload error:', [
//         'error_code' => $uploadError,
//         'error_message' => $errorMessages[$uploadError] ?? 'Unknown error'
//     ]);

//     // Only return if there is a real error (not 0)
//     if ($uploadError && $uploadError !== UPLOAD_ERR_OK) {
//         return response()->json([
//             'error' => ['picture' => [$errorMessages[$uploadError] ?? 'File upload failed']]
//         ], 422);
//     }
//     }


//         $request->validate([
//             'username' => 'required|string',
//             'circle' => 'required|string',
//             'pin' => 'required|string',
//             'info' => 'required|string',
//             'latitude' => 'required|numeric',
//             'longitude' => 'required|numeric',
//             'floors_num' => 'required|integer',
//             'basement' => 'required|integer',
//             'land_area' => 'required|string',
//             'covered_area' => 'required|string',
//             'land' => 'nullable|string',
//             'other' => 'nullable|string',
//             'comments' => 'nullable|string',
//             'picture' => 'required|image|file|max:51200',
//             'capture_time' => 'required|string',
//             'submission_time' => 'required|string',
//             'Store_front' => 'nullable|integer',
//         ]);

//         $user = \App\Models\MobileUser::where('username', $request->username)->first();
//         if (!$user) {
//             Log::error("User not found for username: {$request->username}");
//             return response()->json(['error' => 'User not found'], 404);
//         }
//         if ($request->hasFile('picture')) {
//     $file = $request->file('picture');
//     Log::info('Picture debug', [
//         'isValid' => $file->isValid(),
//         'error'   => $file->getError(), // 0 = OK
//         'size'    => $file->getSize(),
//         'mime'    => $file->getMimeType(),
//         'originalName' => $file->getClientOriginalName(),
//     ]);
//     } else {
//         Log::info('No picture uploaded at all');
//     }



//         $picturePath = null;
//         if ($request->hasFile('picture')) {
//             $picturePath = $request->file('picture')->store('property_images', 'public');
//         }

//         $existingDetail = \App\Models\UserPropertyDetail::where('pin', $request->pin)->first();

//         if ($existingDetail) {
//             $existingDetail->update([
//                 'user_id' => $user->id,
//                 'username' => $request->username,
//                 'circle' => $request->circle,
//                 'info' => $request->info,
//                 'latitude' => $request->latitude,
//                 'longitude' => $request->longitude,
//                 'floors_num' => $request->floors_num,
//                 'basement' => $request->basement,
//                 'land_area' => $request->land_area,
//                 'covered_area' => $request->covered_area,
//                 'land' => $request->land,
//                 'other' => $request->other,
//                 'comments' => $request->comments,
//                 'capture_time' => $request->capture_time,
//                 'submission_time' => $request->submission_time,
//                 'resubmission' => 1,
//                 'Store_front' => $request->input('Store_front', 0),
//             ]);

//             if ($picturePath) {
//                 $existingDetail->picture_path = $picturePath;
//                 $existingDetail->save();
//             }

//             $message = 'Property detail updated successfully';
//         } else {
//             $detail = new \App\Models\UserPropertyDetail([
//                 'user_id' => $user->id,
//                 'username' => $request->username,
//                 'circle' => $request->circle,
//                 'pin' => $request->pin,
//                 'info' => $request->info,
//                 'latitude' => $request->latitude,
//                 'longitude' => $request->longitude,
//                 'floors_num' => $request->floors_num,
//                 'basement' => $request->basement,
//                 'land_area' => $request->land_area,
//                 'covered_area' => $request->covered_area,
//                 'land' => $request->land,
//                 'other' => $request->other,
//                 'comments' => $request->comments,
//                 'picture_path' => $picturePath,
//                 'capture_time' => $request->capture_time,
//                 'submission_time' => $request->submission_time,
//                 'resubmission' => 0,
//                 'Store_front' => $request->input('Store_front', 0),
//             ]);

//             $detail->save();

//             $message = 'Property detail submitted successfully';
//         }

//         \App\Models\UserCirclePin::where('pin', $request->pin)
//             ->update(['status' => true]);

//         return response()->json(['message' => $message], 201);

//     } catch (\Illuminate\Validation\ValidationException $e) {
//         // log validation errors
//         Log::error('Validation failed', [
//             'errors' => $e->errors(),
            
//             'input' => $request->all(),
//         ]);
//         return response()->json(['error' => $e->errors()], 422);

//     } catch (\Exception $e) {
//         // log unexpected exceptions
//         Log::error('Unexpected error in store()', [
//             'message' => $e->getMessage(),
//             'trace' => $e->getTraceAsString(),
//             'input' => $request->all(),
//         ]);
//         return response()->json(['error' => 'Something went wrong, please try again later.'], 500);
//     }
// }

public function store(Request $request)
{
    // Log every incoming request
    Log::info('Incoming request to store()', [
        'input' => $request->all(),
        'files' => $request->allFiles(),
    ]);

    try {
        // ✅ Step 1: Check for raw upload errors before validation
        if ($request->has('picture') && !$request->hasFile('picture')) {
            $uploadError = $_FILES['picture']['error'] ?? null;
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'File exceeds PHP upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit',
                UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'No temporary directory available',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension',
            ];

            Log::error('File upload error', [
                'error_code'    => $uploadError,
                'error_message' => $errorMessages[$uploadError] ?? 'Unknown error',
                'input'         => $request->all(),
            ]);

            if ($uploadError && $uploadError !== UPLOAD_ERR_OK) {
                return response()->json([
                    'error' => ['picture' => [$errorMessages[$uploadError] ?? 'File upload failed']]
                ], 422);
            }
        }

        // ✅ Step 2: Validation
        $validated = $request->validate([
            'username'        => 'required|string',
            'circle'          => 'required|string',
            'pin'             => 'required|string',
            'info'            => 'required|string',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'floors_num'      => 'required|integer',
            'basement'        => 'required|integer',
            'land_area'       => 'required|string',
            'covered_area'    => 'required|string',
            'land'            => 'nullable|string',
            'other'           => 'nullable|string',
            'comments'        => 'nullable|string',
            'picture'         => 'required|image|file|max:51200',
            'picture2'        => 'nullable|image|file|max:51200',
            'capture_time'    => 'nullable|string',
            'submission_time' => 'required|string',
            'Store_front'     => 'nullable|integer',
        ]);

        $validated['capture_time'] = $validated['capture_time'] ?? now()->toDateTimeString();

        // ✅ Step 3: Find user
        $user = \App\Models\MobileUser::where('username', $validated['username'])->first();
        if (!$user) {
            Log::error("User not found", ['username' => $validated['username']]);
            return response()->json(['error' => 'User not found'], 404);
        }

        // ✅ Step 4: Handle picture upload
        $picturePath = null;
        $picture2Path = null;


        if ($request->hasFile('picture')) {
            $file = $request->file('picture');
            Log::info('Picture upload debug', [
                'isValid'       => $file->isValid(),
                'error'         => $file->getError(),
                'size'          => $file->getSize(),
                'mime'          => $file->getMimeType(),
                'originalName'  => $file->getClientOriginalName(),
            ]);

            $picturePath = $file->store('property_images', 'public');
        } else {
            Log::warning('No picture uploaded at all');
        }

        if ($request->hasFile('picture2')) {
            $file2 = $request->file('picture2');
            Log::info('Picture2 upload debug', [
                'key'           => 'picture2',
                'isValid'       => $file2->isValid(),
                'error'         => $file2->getError(),
                'size'          => $file2->getSize(),
                'mime'          => $file2->getMimeType(),
                'originalName'  => $file2->getClientOriginalName(),
            ]);
            $picture2Path = $file2->store('property_images', 'public');
        }

        // ✅ Step 5: Update or create UserPropertyDetail
        // $existingDetail = \App\Models\UserPropertyDetail::where('pin', $validated['pin'])->first();
        $existingDetail = \App\Models\UserPropertyDetail::where('pin', $validated['pin'])
        ->where('username', $validated['username'])
        ->first();


        if ($existingDetail) {
            $existingDetail->update([
                'user_id'        => $user->id,
                'username'       => $validated['username'],
                'circle'         => $validated['circle'],
                'info'           => $validated['info'],
                'latitude'       => $validated['latitude'],
                'longitude'      => $validated['longitude'],
                'floors_num'     => $validated['floors_num'],
                'basement'       => $validated['basement'],
                'land_area'      => $validated['land_area'],
                'covered_area'   => $validated['covered_area'],
                'land'           => $validated['land'] ?? null,
                'other'          => $validated['other'] ?? null,
                'comments'       => $validated['comments'] ?? null,
                'capture_time'   => $validated['capture_time'],
                'submission_time'=> $validated['submission_time'],
                'resubmission'   => 1,
                'Store_front'    => $validated['Store_front'] ?? 0,
            ]);

            if ($picturePath) {
                $existingDetail->picture_path = $picturePath;
                
            }
            if ($picture2Path) {
                $existingDetail->picture2_path = $picture2Path;
            }
            $existingDetail->save();

            $message = 'Property detail updated successfully';
        } else {
            \App\Models\UserPropertyDetail::create([
                'user_id'        => $user->id,
                'username'       => $validated['username'],
                'circle'         => $validated['circle'],
                'pin'            => $validated['pin'],
                'info'           => $validated['info'],
                'latitude'       => $validated['latitude'],
                'longitude'      => $validated['longitude'],
                'floors_num'     => $validated['floors_num'],
                'basement'       => $validated['basement'],
                'land_area'      => $validated['land_area'],
                'covered_area'   => $validated['covered_area'],
                'land'           => $validated['land'] ?? null,
                'other'          => $validated['other'] ?? null,
                'comments'       => $validated['comments'] ?? null,
                'picture_path'   => $picturePath,
                'picture2_path'  => $picture2Path,
                'capture_time'   => $validated['capture_time'],
                'submission_time'=> $validated['submission_time'],
                'resubmission'   => 0,
                'Store_front'    => $validated['Store_front'] ?? 0,
            ]);

            $message = 'Property detail submitted successfully';
        }

        // ✅ Step 6: Update UserCirclePin status
        \App\Models\UserCirclePin::where('pin', $validated['pin'])
            ->update(['status' => true]);

        Log::info("Store success", [
            'username' => $validated['username'],
            'pin'      => $validated['pin'],
            'message'  => $message,
        ]);

        return response()->json(['message' => $message], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed', [
            'errors' => $e->errors(),
            'input'  => $request->all(),
        ]);
        return response()->json(['error' => $e->errors()], 422);

    } catch (\Exception $e) {
        Log::error('Unexpected error in store()', [
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
            'input'   => $request->all(),
        ]);
        return response()->json(['error' => 'Something went wrong, please try again later.'], 500);
    }
}





public function update(Request $request)
{
    $request->validate([
        'id' => 'required|integer', // Property detail ID
        'username' => 'required|string',
        'circle' => 'required|string',
        'pin' => 'required|string',
        'info' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'floors_num' => 'required|integer',
        'basement' => 'required|integer',
        'land_area' => 'required|string',
        'covered_area' => 'required|string',
        'land' => 'nullable|string',
        'other' => 'nullable|string',
        'comments' => 'nullable|string',
        'picture' => 'nullable|image',
        'capture_time' => 'nullable|string',
        'submission_time' => 'required|string'
    ]);

    $detail = UserPropertyDetail::find($request->id);
    if (!$detail) {
        return response()->json(['error' => 'Property detail not found'], 404);
    }

    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    if ($request->hasFile('picture')) {
        $picturePath = $request->file('picture')->store('property_images', 'public');
        $detail->picture_path = $picturePath;
    }

    $detail->user_id = $user->id;
    $detail->username = $request->username;
    $detail->circle = $request->circle;
    $detail->pin = $request->pin;
    $detail->info = $request->info;
    $detail->latitude = $request->latitude;
    $detail->longitude = $request->longitude;
    $detail->floors_num = $request->floors_num;
    $detail->basement = $request->basement;
    $detail->land_area = $request->land_area;
    $detail->covered_area = $request->covered_area;
    $detail->land = $request->land;
    $detail->other = $request->other;
    $detail->comments = $request->comments;
    $detail->capture_time = $request->capture_time;
    $detail->submission_time = $request->submission_time;

    $detail->save();

    // ✅ Update the pin status if needed
    \App\Models\UserCirclePin::where('user_id', $user->id)
        ->where('pin', $request->pin)
        ->update(['status' => true]);

    return response()->json(['message' => 'Property detail updated via POST successfully'], 200);
}




public function reports()
{

    $propertyDetails = UserPropertyDetail::all(); // Get all records from UserPropertyDetail

    if ($propertyDetails->isEmpty()) {
        return response()->json(['message' => 'No property details found'], 404);
    }

    return response()->json(['data' => $propertyDetails], 200);
}

// public function report()
// {
//     $propertyDetails = UserPropertyDetail::with('user')
//         ->whereNotIn('username', ['farhan001', 'kabir002'])
//         ->get(); // Eager load user and filter usernames

//     if ($propertyDetails->isEmpty()) {
//         return response()->json(['message' => 'No property details found'], 404);
//     }

//     $reportData = $propertyDetails->map(function ($detail) {
//         return [
//             'id' => $detail->id,
//             'username' => $detail->username,
//             'circle' => $detail->circle,
//             'pin' => $detail->pin,
//             'info' => $detail->info,
//             'latitude' => $detail->latitude,
//             'longitude' => $detail->longitude,
//             'floors_num' => $detail->floors_num,
//             'basement' => $detail->basement,
//             'land_area' => $detail->land_area,
//             'covered_area' => $detail->covered_area,
//             'land' => $detail->land,
//             'other' => $detail->other,
//             'comments' => $detail->comments,
//             'picture_path' => $detail->picture_path,
//             'capture_time' => $detail->capture_time,
//             'submission_time' => $detail->submission_time,
//             // Extra user info
//             'user_name' => $detail->user->name ?? null,
//             'user_username' => $detail->user->username ?? null,
//             'user_phone' => $detail->user->phonenumber ?? null,
//             'resubmission' => $detail->resubmission ?? 0,
//             'Store_front' => $detail->Store_front ?? 0,
//         ];
//     });

//     return response()->json(['data' => $reportData], 200);
// }

public function reportofUser(Request $request)
{
    $request->validate([
        'username' => 'required|string'
    ]);

    $user = \App\Models\MobileUser::where('username', $request->username)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $propertyDetails = UserPropertyDetail::with('user')
        ->where('user_id', $user->id)
        ->get();

    if ($propertyDetails->isEmpty()) {
        return response()->json(['message' => 'No property details found for this user'], 404);
    }

    $reportData = $propertyDetails->map(function ($detail) {
        return [
            'id' => $detail->id,
            'username' => $detail->username,
            'circle' => $detail->circle,
            'pin' => $detail->pin,
            'info' => $detail->info,
            'latitude' => $detail->latitude,
            'longitude' => $detail->longitude,
            'floors_num' => $detail->floors_num,
            'basement' => $detail->basement,
            'land_area' => $detail->land_area,
            'covered_area' => $detail->covered_area,
            'land' => $detail->land,
            'other' => $detail->other,
            'comments' => $detail->comments,
            'picture_path' => $detail->picture_path,
            'capture_time' => $detail->capture_time,
            'submission_time' => $detail->submission_time,
            // Optional user info
            'user_name' => $detail->user->name ?? null,
            'user_username' => $detail->user->username ?? null,
            'user_phone' => $detail->user->phonenumber ?? null,
        ];
    });

    return response()->json(['data' => $reportData], 200);
}


public function reportMultipleUsers(Request $request)
{
    $request->validate([
        'usernames' => 'required|array|min:1|max:2',
        'usernames.*' => 'required|string'
    ]);

    $users = \App\Models\MobileUser::whereIn('username', $request->usernames)->get();

    if ($users->isEmpty()) {
        return response()->json(['error' => 'No matching users found'], 404);
    }

    $userIds = $users->pluck('id');

    $propertyDetails = UserPropertyDetail::with('user')
        ->whereIn('user_id', $userIds)
        ->get();

    if ($propertyDetails->isEmpty()) {
        return response()->json(['message' => 'No property details found for the given users'], 404);
    }

    $reportData = $propertyDetails->map(function ($detail) {
        return [
            'id' => $detail->id,
            'username' => $detail->username,
            'circle' => $detail->circle,
            'pin' => $detail->pin,
            'info' => $detail->info,
            'latitude' => $detail->latitude,
            'longitude' => $detail->longitude,
            'floors_num' => $detail->floors_num,
            'basement' => $detail->basement,
            'land_area' => $detail->land_area,
            'covered_area' => $detail->covered_area,
            'land' => $detail->land,
            'other' => $detail->other,
            'comments' => $detail->comments,
            'picture_path' => $detail->picture_path,
            'capture_time' => $detail->capture_time,
            'submission_time' => $detail->submission_time,
            // Extra user info
            'user_name' => $detail->user->name ?? null,
            'user_username' => $detail->user->username ?? null,
            'user_phone' => $detail->user->phonenumber ?? null,
        ];
    });

    return response()->json(['data' => $reportData], 200);
}



public function totalreports()
{
    return UserPropertyDetail::count();
}





public function deleteReportsByUsername(Request $request)
{
    $request->validate([
        'username' => 'required|string'
    ]);

    $user = \App\Models\MobileUser::where('username', $request->username)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Delete all reports of this user
    $deleted = \App\Models\UserPropertyDetail::where('username', $request->username)->delete();

    if ($deleted > 0) {
        return response()->json(['message' => "Reports for {$request->username} deleted successfully"], 200);
    } else {
        return response()->json(['message' => "No reports found for {$request->username}"], 404);
    }
}


































public function report(Request $request) 
{
    // Optimized: Only load necessary columns from user relationship
    $query = UserPropertyDetail::with(['user' => function($q) {
        $q->select('id', 'name', 'username', 'phonenumber');
    }])->whereNotIn('username', ['farhan001', 'kabir002']);
    
    // Apply filters
    $query = $this->applyFilters($query, $request);
    
    // Get pagination parameters
    $perPage = $request->get('per_page', 10);
    $page = $request->get('page', 1);
    
    // Paginate the results
    $propertyDetails = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
    
    if ($propertyDetails->isEmpty()) {
        return response()->json([
            'data' => [],
            'current_page' => 1,
            'last_page' => 1,
            'total' => 0
        ], 200);
    }
    
    $reportData = $propertyDetails->map(function ($detail) {
        return $this->formatPropertyData($detail);
    });
    
    return response()->json([
        'data' => $reportData,
        'current_page' => $propertyDetails->currentPage(),
        'last_page' => $propertyDetails->lastPage(),
        'total' => $propertyDetails->total(),
        'per_page' => $propertyDetails->perPage(),
        'from' => $propertyDetails->firstItem(),
        'to' => $propertyDetails->lastItem()
    ], 200);
}


// Method to get all properties without pagination (for filter options)
public function reportFilterOptions()
{
    // Optimized: Only load necessary columns from user relationship and main table
    // This reduces memory usage and query time significantly
    $propertyDetails = UserPropertyDetail::with(['user' => function($q) {
            $q->select('id', 'name', 'username', 'phonenumber');
        }])
        ->whereNotIn('username', ['farhan001', 'kabir002'])
        ->select([
            'id', 'user_id', 'username', 'circle', 'pin', 'info', 'latitude', 'longitude', 
            'floors_num', 'basement', 'land_area', 'covered_area', 'land', 
            'other', 'comments', 'picture_path', 'picture2_path', 
            'capture_time', 'submission_time', 'resubmission', 'Store_front'
        ])
        ->get();
    
    if ($propertyDetails->isEmpty()) {
        return response()->json(['data' => []], 200);
    }
    
    $reportData = $propertyDetails->map(function ($detail) {
        return $this->formatPropertyData($detail);
    });
    
    return response()->json(['data' => $reportData], 200);
}

// Method to export filtered data (all results without pagination)
public function reportExport(Request $request)
{
    // Optimized: Only load necessary columns from user relationship
    $query = UserPropertyDetail::with(['user' => function($q) {
        $q->select('id', 'name', 'username', 'phonenumber');
    }])->whereNotIn('username', ['farhan001', 'kabir002']);
    
    // Apply filters
    $query = $this->applyFilters($query, $request);
    
    $propertyDetails = $query->orderBy('id', 'desc')->get();
    
    $reportData = $propertyDetails->map(function ($detail) {
        return $this->formatPropertyData($detail);
    });
    
    return response()->json(['data' => $reportData], 200);
}

// Helper method to apply filters
private function applyFilters($query, Request $request)
{
    // Date filter (single date in DD/MM/YYYY format)
    if ($request->has('date') && !empty($request->get('date'))) {
        $dateFilter = $request->get('date');
        $query->whereRaw("DATE_FORMAT(STR_TO_DATE(submission_time, '%d/%m/%Y %H:%i:%s'), '%d/%m/%Y') = ?", [$dateFilter]);
    }
    
    // Date range filters
    if ($request->has('date_from') && !empty($request->get('date_from'))) {
        $dateFrom = $request->get('date_from'); // YYYY-MM-DD format from frontend
        $query->whereRaw("STR_TO_DATE(submission_time, '%d/%m/%Y %H:%i:%s') >= ?", [$dateFrom]);
    }
    
    if ($request->has('date_to') && !empty($request->get('date_to'))) {
        $dateTo = $request->get('date_to'); // YYYY-MM-DD format from frontend
        $query->whereRaw("STR_TO_DATE(submission_time, '%d/%m/%Y %H:%i:%s') <= ?", [$dateTo . ' 23:59:59']);
    }
    
    // Username filter
    if ($request->has('username') && !empty($request->get('username'))) {
        $query->where('username', $request->get('username'));
    }
    
    // Land type filter
    if ($request->has('land') && !empty($request->get('land'))) {
        $landTypes = explode(',', $request->get('land'));
        $query->whereIn('land', $landTypes);
    }

    // Resubmission filter
    if ($request->has('resubmission') && $request->get('resubmission') !== '') {
    $value = $request->get('resubmission');

    if ($value === 'Yes' || $value === '1' || $value === 1 || $value === true || $value === 'true') {
        $query->where('resubmission', 1);
    } elseif ($value === 'No' || $value === '0' || $value === 0 || $value === false || $value === 'false') {
        $query->where('resubmission', 0);
    }
}

    
    // Name filter
    if ($request->has('name') && !empty($request->get('name'))) {
        $names = explode(',', $request->get('name'));
        $query->whereHas('user', function($q) use ($names) {
            $q->whereIn('name', $names);
        });
    }
    
    // PIN filter
    if ($request->has('pin') && !empty($request->get('pin'))) {
        $pins = explode(',', $request->get('pin'));
        $query->whereIn('pin', $pins);
    }
    
    // Circle filter
    if ($request->has('circle') && !empty($request->get('circle'))) {
        $circles = explode(',', $request->get('circle'));
        $query->whereIn('circle', $circles);
    }
    
    // Basement filter
    if ($request->has('basement') && !empty($request->get('basement'))) {
        $basementValues = explode(',', $request->get('basement'));
        $basementConditions = [];
        
        foreach ($basementValues as $value) {
            if ($value === 'Yes') {
                $basementConditions[] = 1;
            } elseif ($value === 'No') {
                $basementConditions[] = 0;
            }
        }
        
        if (!empty($basementConditions)) {
            $query->whereIn('basement', $basementConditions);
        }
    }
    
    // Floors filter
    if ($request->has('floors') && !empty($request->get('floors'))) {
        $floors = explode(',', $request->get('floors'));
        $query->whereIn('floors_num', $floors);
    }
    
    // Land area range filter
    if ($request->has('land_area') && !empty($request->get('land_area'))) {
        $ranges = explode(',', $request->get('land_area'));
        $query->where(function($q) use ($ranges) {
            foreach ($ranges as $range) {
                list($min, $max) = explode('-', $range);
                $q->orWhereBetween('land_area', [(int)$min, (int)$max]);
            }
        });
    }
    
    // Covered area range filter
    if ($request->has('covered_area') && !empty($request->get('covered_area'))) {
        $ranges = explode(',', $request->get('covered_area'));
        $query->where(function($q) use ($ranges) {
            foreach ($ranges as $range) {
                list($min, $max) = explode('-', $range);
                $q->orWhereBetween('covered_area', [(int)$min, (int)$max]);
            }
        });
    }
    
    return $query;
}

// Helper method to format property data consistently
private function formatPropertyData($detail)
{
    return [
        'id' => $detail->id,
        'username' => $detail->username,
        'circle' => $detail->circle,
        'pin' => $detail->pin,
        'info' => $detail->info,
        'latitude' => $detail->latitude,
        'longitude' => $detail->longitude,
        'floors_num' => $detail->floors_num,
        'basement' => $detail->basement,
        'land_area' => $detail->land_area,
        'covered_area' => $detail->covered_area,
        'land' => $detail->land,
        'other' => $detail->other,
        'comments' => $detail->comments,
        'picture_path' => $detail->picture_path,
        'picture2_path' => $detail->picture2_path,
        'capture_time' => $detail->capture_time,
        'submission_time' => $detail->submission_time,
        // Extra user info
        'user_name' => $detail->user->name ?? null,
        'user_username' => $detail->user->username ?? null,
        'user_phone' => $detail->user->phonenumber ?? null,
        'resubmission' => $detail->resubmission ?? 0,
        'Store_front' => $detail->Store_front ?? 0,
    ];
}







}
