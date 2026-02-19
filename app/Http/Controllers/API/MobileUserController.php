<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MobileUserController extends Controller
{
    // Signup
    public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'username' => 'required|unique:mobile_users',
        'email' => 'required|email|unique:mobile_users',
        'password' => 'required|min:6',
        'cnic' => 'required',
        'name' => 'required',
        'phonenumber' => 'required|string',
        'designation' => 'required|string',
        'status' => 'boolean'
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = MobileUser::create([
        'username' => $request->username,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'cnic' => $request->cnic,
        'name' => $request->name,
        'phonenumber' => $request->phonenumber,
        'designation' => $request->designation,
        'status' => $request->status
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'User registered successfully',
        'access_token' => $token,
        'user' => $user
    ]);
}


    // Login
    public function login(Request $request)
{
    $user = MobileUser::where('username', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    if (! $user->status) {
        return response()->json(['error' => 'Your account is blocked. Please contact support.'], 403);
    }

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'access_token' => $token,
        'user' => $user
    ]);
}


    // Profile (Optional)
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

                // Get all mobile users
    public function getAllUsers()
    {
        $users = \App\Models\MobileUser::all();
        return response()->json([
        'users' => $users
    ]);
}

// public function noUsers()
// {
//     return \App\Models\MobileUser::count();
// }
public function noUsers()
{
    return \App\Models\MobileUser::where('status','!=', 'active')->count();
}


public function changeUserStatus(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:mobile_users,id',
        'status' => 'required|boolean'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = MobileUser::find($request->user_id);
    $user->status = $request->status;
    $user->save();

    return response()->json([
        'message' => $request->status ? 'User unblocked successfully.' : 'User blocked successfully.',
        'user' => $user
    ]);
}


public function deleteUser(Request $request)
{
    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:mobile_users,id'
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $user = MobileUser::find($request->user_id);

    // Optional: prevent deleting certain users, e.g., admins
    // if ($user->is_admin) {
    //     return response()->json(['error' => 'Cannot delete admin user'], 403);
    // }

    $user->delete();

    return response()->json([
        'message' => 'User deleted successfully'
    ]);
}


}
