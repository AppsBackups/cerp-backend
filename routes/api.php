<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\MobileUserController;
use App\Http\Controllers\API\PropertyRecordController;
use App\Http\Controllers\API\UserCircleController;
use App\Http\Controllers\UserPropertyDetailController;
use App\Http\Controllers\ExcelUploadController;



Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'create']);
Route::middleware('auth')->post('logout', [AuthController::class, 'logout']);
Route::middleware('auth')->get('current-user', [AuthController::class, 'getCurrentUser']);



Route::post('/mobile/signup', [MobileUserController::class, 'register']);
Route::post('/mobile/login', [MobileUserController::class, 'login']);
Route::get('/mobile/users', [MobileUserController::class, 'getAllUsers']);
Route::post('/store-records', [PropertyRecordController::class, 'store']);
Route::get('/mobileusers', [MobileUserController::class, 'noUsers']);
Route::post('/change-user-status', [MobileUserController::class, 'changeUserStatus']);
Route::post('/delete-user', [MobileUserController::class, 'deleteUser']);





// Step 1: Get unique circles
Route::get('/circles/{username}', [UserCircleController::class, 'getCircles']);
Route::get('/totalrecord', [UserCircleController::class, 'getnum']);
Route::post('/store-circle-pins', [UserCircleController::class, 'storeCircleAndPins']);
Route::post('/replace-circle-pins', [UserCircleController::class, 'replaceCircleAndPins']);
Route::post('/add-pin', [UserCircleController::class, 'addpin']);
Route::post('/change-pin-status', [UserCircleController::class, 'changePinStatus']);
Route::post('/change-status-bulk', [UserCircleController::class, 'bulkChangePinStatus']);



Route::post('/pins-by-circle', [UserCircleController::class, 'getPinsByCircle']);
Route::post('/pins-by-circle/v2', [UserCircleController::class, 'getPinsByCircle2']);
Route::post('/circles-assigned-to-user', [UserCircleController::class, 'postCirclesAssignedToUser']);
Route::post('/circle-pin-info', [UserCircleController::class, 'postCirclePinInfo']);
Route::post('/delete-pin',[UserCircleController::class, 'deletepin']);
Route::post('/delete-circle',[UserCircleController::class, 'deletecircle']);
Route::post('/transfer-pins', [UserCircleController::class, 'transferPins']);

Route::post('/submit-property', [UserPropertyDetailController::class, 'store']);
Route::delete('/reports/delete-by-username', [UserPropertyDetailController::class, 'deleteReportsByUsername']);



Route::get('/all-reports', [UserPropertyDetailController::class, 'report']);
Route::get('/all-reports-no-pagination', [UserPropertyDetailController::class, 'reportFilterOptions']);
Route::get('/all-reports-export', [UserPropertyDetailController::class, 'reportExport']);

Route::post('/submit-property-both', [UserPropertyDetailController::class, 'stores']);
// Route::get('all-reports', [UserPropertyDetailController::class, 'report']);
Route::get('noreports', [UserPropertyDetailController::class, 'totalreports']);
Route::post('/property-details/update', [UserPropertyDetailController::class, 'update']);
Route::post('/property-details/report', [UserPropertyDetailController::class, 'reportofUser']);
Route::post('/property-details/report-multiple', [UserPropertyDetailController::class, 'reportMultipleUsers']);




Route::post('/location-upload', [ExcelUploadController::class, 'store']);




// Protected route example
Route::middleware('auth:sanctum')->get('/mobile/profile', [MobileUserController::class, 'profile']);
