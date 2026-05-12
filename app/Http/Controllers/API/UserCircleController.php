<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MobileUser;
use Illuminate\Http\Request;
use App\Models\PropertyRecord;
use App\Models\UserCirclePin;
use App\Models\UserPropertyDetail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserCircleController extends Controller
{
    // Step 1: Get unique circles by username
    public function getCircles($username)
    {
        $user = MobileUser::where('username', $username)->first();
        if (!$user) return response()->json(['error' => 'User not found'], 404);

        // Optimized: Cache the circles list for 60 minutes as it doesn't change frequently
        $circles = \Illuminate\Support\Facades\Cache::remember('distinct_circles', 3600, function () {
            return PropertyRecord::distinct()->pluck('circle');
        });
        
        return response()->json(['circles' => $circles]);
    }


    public function postCirclesAssignedToUser(Request $request)
    {
        // Validate that the 'username' is provided in the request body
        $request->validate([
            'username' => 'required|string'
        ]);

        // Retrieve the user by the provided username
        $user = MobileUser::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Retrieve distinct circles assigned to the user
        $circles = UserCirclePin::where('user_id', $user->id)
                                ->distinct()
                                ->pluck('circle');

        // Return the circles as a response
        return response()->json(['circles' => $circles]);
    }

    /**
     * Get circles for multiple users with pagination support
     */
    public function getCirclesForAllUsers(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'usernames' => 'nullable|array',
                'usernames.*' => 'string',
                'page' => 'nullable|integer',
                'per_page' => 'nullable|integer'
            ]);
            
            $perPage = $request->get('per_page', 20);
            
            // Get paginated users first
            $userQuery = MobileUser::orderBy('id', 'desc');
            
            if ($request->has('usernames') && !empty($request->usernames)) {
                $userQuery->whereIn('username', $request->usernames);
            }
            
            $paginatedUsers = $userQuery->paginate($perPage);
            $usernames = $paginatedUsers->pluck('username')->toArray();
            $userIdToUsername = $paginatedUsers->pluck('username', 'id')->toArray();
            
            if (empty($userIdToUsername)) {
                return response()->json([
                    'data' => [],
                    'current_page' => $paginatedUsers->currentPage(),
                    'last_page' => $paginatedUsers->lastPage(),
                    'total' => $paginatedUsers->total()
                ]);
            }
            
            // Get all circle assignments for these users in a SINGLE query
            $userIds = array_keys($userIdToUsername);
            $assignments = DB::table('user_circle_pins')
                ->whereIn('user_id', $userIds)
                ->select('user_id', 'circle')
                ->distinct()
                ->get()
                ->groupBy('user_id')
                ->map(function ($items) {
                    return $items->pluck('circle')->toArray();
                });
            
            // Prepare response with usernames as keys
            $result = [];
            foreach ($usernames as $username) {
                // Find user_id for this username
                $userId = array_search($username, $userIdToUsername);
                if ($userId && isset($assignments[$userId])) {
                    $result[$username] = $assignments[$userId];
                } else {
                    $result[$username] = [];
                }
            }
            
            return response()->json([
                'data' => $result,
                'current_page' => $paginatedUsers->currentPage(),
                'last_page' => $paginatedUsers->lastPage(),
                'total' => $paginatedUsers->total(),
                'per_page' => $paginatedUsers->perPage()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Batch circles error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch circles',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // Step 2: Get pins by selected circle
//     public function getPinsByCircle(Request $request)
// {
//     $request->validate([
//         'username' => 'required',
//         'circle' => 'required'
//     ]);

//     $user = MobileUser::where('username', $request->username)->first();
//     if (!$user) return response()->json(['error' => 'User not found'], 404);

//     $pins = UserCirclePin::where('user_id', $user->id)
//         ->where('circle', $request->circle)
//         ->where('status', false)
//         ->get();

//     return response()->json([
//         'circle' => $request->circle,
//         'pins' => $pins
//     ]);
// }




public function getPinsByCircle(Request $request)
{
    $request->validate([
        'username' => 'required',
        'circle' => 'required'
    ]);

    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $usedPins = DB::table('user_property_details')
        ->where('username', '!=', $request->username)
        ->select('pin')
        ->distinct();

    $pins = DB::table('user_circle_pins as ucp')
        ->leftJoin('locations as l', 'l.pin', '=', 'ucp.pin')
        ->leftJoinSub($usedPins, 'used', function ($join) {
            $join->on('used.pin', '=', 'ucp.pin');
        })
        ->where('ucp.user_id', $user->id)
        ->where('ucp.circle', $request->circle)
        ->where('ucp.status', 0)
        ->whereNull('used.pin')
        ->select(
            'ucp.id',
            'ucp.pin',
            'ucp.circle',
            'ucp.ratingarea',
            'ucp.Locality',
            'ucp.Road',
            'ucp.Block',
            'ucp.Street_Address',
            'ucp.OwnerName',
            'ucp.status',
            'ucp.user_id',
            
            'l.lat',
            'l.lng'
        )
        ->get();

    return response()->json([
        'circle' => $request->circle,
        'count' => $pins->count(),
        'pins' => $pins
    ]);
}

public function getPinsByCircle2(Request $request)
{
    $request->validate([
        'username' => 'required',
        'circle' => 'required',
        'search' => 'nullable|string',
        'page' => 'nullable|integer',
        'per_page' => 'nullable|integer'
    ]);

    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $usedPins = DB::table('user_property_details')
        ->where('username', '!=', $request->username)
        ->select('pin')
        ->distinct();

    $query = DB::table('user_circle_pins as ucp')
        ->leftJoin('locations as l', 'l.pin', '=', 'ucp.pin')
        ->leftJoinSub($usedPins, 'used', function ($join) {
            $join->on('used.pin', '=', 'ucp.pin');
        })
        ->where('ucp.user_id', $user->id)
        ->where('ucp.circle', $request->circle)
        ->where('ucp.status', 0)
        ->whereNull('used.pin');

    // Typeahead search: only apply if 4+ characters typed
    if ($request->filled('search') && strlen($request->search) >= 4) {
        $query->where('ucp.pin', 'like', $request->search . '%');
    }

    $perPage = $request->per_page ?? 20;

    $pins = $query->select(
            'ucp.id',
            'ucp.pin',
            'ucp.circle',
            'ucp.ratingarea',
            'ucp.Locality',
            'ucp.Road',
            'ucp.Block',
            'ucp.Street_Address',
            'ucp.OwnerName',
            'ucp.status',
            'ucp.user_id',
            'l.lat',
            'l.lng'
        )
        ->orderBy('ucp.id', 'desc')
        ->paginate($perPage)
        ->appends($request->only('search', 'per_page')); // keep search in links

    return response()->json([
        'circle' => $request->circle,
        'count' => $pins->total(),
        'current_page' => $pins->currentPage(),
        'per_page' => $pins->perPage(),
        'pins' => $pins->items()
    ]);
}



    // Step 3: Save selected circle + all pins for user
    public function storeCircleAndPins(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'circle' => 'required'
        ]);

        $user = MobileUser::where('username', $request->username)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $records = PropertyRecord::where('circle', $request->circle)->get();
        $usedPinSet = UserPropertyDetail::where('circle', $request->circle)
            ->distinct()
            ->pluck('pin')
            ->flip();

        $dataToInsert = [];
        $now = now();

        foreach ($records as $record) {
            $dataToInsert[] = [
                'user_id' => $user->id,
                'pin' => $record->pin,
                'circle' => $record->circle,
                'ratingarea' => $record->ratingarea,
                'Locality' => $record->Locality,
                'Block' => $record->Block,
                'Street_Address' => $record->Street_Address,
                'OwnerName' => $record->OwnerName,
                'Road' => $record->Road,
                'status' => $usedPinSet->has($record->pin),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Use chunked inserts to handle large datasets efficiently
        $chunks = array_chunk($dataToInsert, 500);
        foreach ($chunks as $chunk) {
            // Using upsert or updateOrCreate in bulk is complex in Eloquent for this structure,
            // but since we want to "updateOrCreate", we can use DB::table()->upsert if supported
            // or just insert ignore if that's the intent. 
            // Given the original code used updateOrCreate on (user_id, pin), we'll use upsert.
            DB::table('user_circle_pins')->upsert(
                $chunk, 
                ['user_id', 'pin'], 
                ['circle', 'ratingarea', 'Locality', 'Block', 'Street_Address', 'OwnerName', 'Road', 'updated_at']
            );
        }

        return response()->json(['message' => 'Circle and all related pins saved successfully']);
    }


    public function replaceCircleAndPins(Request $request)
{
    $request->validate([
        'username' => 'required',
        'circle' => 'required'
    ]);

    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Step 1: Delete all existing pins for this user and circle
    UserCirclePin::where('user_id', $user->id)
                 ->where('circle', $request->circle)
                 ->delete();

    // Step 2: Fetch all records for the new circle
    $records = PropertyRecord::where('circle', $request->circle)->get();
    $usedPinSet = UserPropertyDetail::where('circle', $request->circle)
        ->distinct()
        ->pluck('pin')
        ->flip();

    // Step 3: Insert all new pins for this circle in bulk
    $dataToInsert = [];
    $now = now();
    foreach ($records as $record) {
        $dataToInsert[] = [
            'user_id' => $user->id,
            'pin' => $record->pin,
            'circle' => $record->circle,
            'ratingarea' => $record->ratingarea,
            'Locality' => $record->Locality,
            'Block' => $record->Block,
            'Street_Address' => $record->Street_Address,
            'OwnerName' => $record->OwnerName,
            'Road' => $record->Road,
            'status' => $usedPinSet->has($record->pin),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    // Use chunked inserts to handle large datasets
    $chunks = array_chunk($dataToInsert, 500);
    foreach ($chunks as $chunk) {
        DB::table('user_circle_pins')->upsert(
            $chunk,
            ['user_id', 'pin'],
            ['circle', 'ratingarea', 'Locality', 'Block', 'Street_Address', 'OwnerName', 'Road', 'updated_at']
        );
    }

    return response()->json(['message' => 'Circle pins replaced successfully']);
}


    // Step 4: Delete circle assignment

    public function deletecircle(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'circle' => 'required|string|max:255'
        ]);

        $user = MobileUser::where('username', $request->username)->first();

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Delete the assignment
            $deleted = DB::table('user_circle_pins')
                ->where('user_id', $user->id)
                ->where('circle', $request->circle)
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Circle assignment removed successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Circle assignment not found'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }




public function addpin(Request $request) {
    $validated = $request->validate([
        'username' => 'required|string',
        'circle' => 'required|string',
        'pin' => 'required|string',
        'ratingarea' => 'required|string',
        'Locality' => 'required|string',
        'Block' => 'nullable|string',
        'Street_Address' => 'required|string',
        'OwnerName' => 'required|string',
        'Road' => 'nullable|string',
        'status' => 'required|integer|in:0,1',
    ]);

    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    try {
        $isUsed = UserPropertyDetail::where('pin', $request->pin)
            ->where('username', '!=', $request->username)
            ->exists();

        $pin = UserCirclePin::create([
            'user_id' => $user->id,
            'circle' => $request->circle,
            'pin' => $request->pin,
            'ratingarea' => $request->ratingarea,
            'Locality' => $request->Locality,
            'Block' => $request->Block,
            'Street_Address' => $request->Street_Address,
            'OwnerName' => $request->OwnerName,
            'Road' => $request->Road,
            'status' => $isUsed ? 1 : $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pin added successfully',
            'pin' => $pin
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to add pin: ' . $e->getMessage()
        ], 500);
    }
}


public function changePinStatus(Request $request)
{
    $validated = $request->validate([
        'username' => 'required|string',
        'circle'   => 'required|string',
        'pin'      => 'required|string',
        
    ]);

    // Find user
    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    // Find pin by circle + pin + user_id
    $pin = UserCirclePin::where('user_id', $user->id)
        ->where('circle', $request->circle)
        ->where('pin', $request->pin)
        ->first();

    if (!$pin) {
        return response()->json(['error' => 'Pin not found'], 404);
    }

    try {
        $pin->status = $request->status;
        $pin->save();

        return response()->json([
            'success' => true,
            'message' => 'Pin status updated successfully',
            'pin' => $pin
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update pin status: ' . $e->getMessage()
        ], 500);
    }
}






public function deletepin(Request $request) {
    try {
        $request->validate([
            'id' => 'required'
        ]);

        $pin = UserCirclePin::findOrFail($request->id);
        $pin->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pin deleted successfully'
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation error',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}

public function postCirclePinInfo(Request $request)
{
    $request->validate([
        'username' => 'required',
        'circle' => 'required|string',
        'pin' => 'required|string'
    ]);

    $user = MobileUser::where('username', $request->username)->first();
    if (!$user) return response()->json(['error' => 'User not found'], 404);

    $data = UserCirclePin ::where('user_id', $user->id)
        ->where('circle', $request->circle)
        ->where('pin', $request->pin)
        ->get();

    if ($data->isEmpty()) {
        return response()->json(['message' => 'No records found'], 404);
    }

    return response()->json(['records' => $data], 200);
}




public function transferPins(Request $request)
{
    $request->validate([
        'source_user' => 'required|string',
        'target_user' => 'required|string|different:source_user',
        'circle' => 'required|string'
    ]);

    // Fetch users
    $sourceUser = MobileUser::where('username', $request->source_user)->first();
    if (!$sourceUser) {
        return response()->json(['error' => 'Source user not found'], 404);
    }

    $targetUser = MobileUser::where('username', $request->target_user)->first();
    if (!$targetUser) {
        return response()->json(['error' => 'Target user not found'], 404);
    }

    // Fetch all pins belonging to the source user's circle
    $sourcePins = UserCirclePin::where('user_id', $sourceUser->id)
        ->where('circle', $request->circle)
        ->get();

    if ($sourcePins->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No pins found to transfer'
        ]);
    }

    // Prepare new pins for target user
    $newPins = [];
    foreach ($sourcePins as $pin) {
        $newPins[] = [
            'user_id' => $targetUser->id,
            'circle' => $pin->circle,
            'pin' => $pin->pin,
            'ratingarea' => $pin->ratingarea,
            'Locality' => $pin->Locality,
            'Block' => $pin->Block,
            'Street_Address' => $pin->Street_Address,
            'OwnerName' => $pin->OwnerName,
            'Road' => $pin->Road,
            'status' => $pin->status,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    // Insert pins in a transaction
    DB::beginTransaction();
    try {
        UserCirclePin::insert($newPins);
        DB::commit();

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to transfer pins: ' . $e->getMessage()
        ]);
    }
}


public function bulkChangePinStatus(Request $request)
{
    $validated = $request->validate([
        'pins' => 'required|array',
        'pins.*.circle' => 'required|string',
        'pins.*.pin'    => 'required|string',
        'pins.*.status' => 'required|in:0,1', // enforce only 0 or 1
    ]);

    $updated = 0;
    $notFound = [];

    foreach ($request->pins as $row) {
        // update all rows matching circle + pin
        $affected = \App\Models\UserCirclePin::where('circle', $row['circle'])
            ->where('pin', $row['pin'])
            ->update(['status' => $row['status']]);

        if ($affected > 0) {
            $updated += $affected; // count all rows updated
        } else {
            $notFound[] = $row;
        }
    }

    return response()->json([
        'success'   => true,
        'message'   => "Processed " . count($request->pins) . " pins. Updated: $updated. Not found: " . count($notFound),
        'not_found' => $notFound
    ]);
}


public function getnum()
{
    return PropertyRecord::count();
}


}
