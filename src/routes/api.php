<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\TownshipController;
use App\Http\Controllers\Api\StateRegionController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::resources([
        'state-regions' => StateRegionController::class,
        'districts' => DistrictController::class,
        'townships' => TownshipController::class
    ]);

    Route::get('/user/profile', [UserController::class, 'show']);
    Route::put('/user/profile', [UserController::class, 'update']);
    Route::get('/user/types', [UserController::class, 'userTypes']);
    Route::get('/user/location', [UserController::class, 'location']);
});
Route::middleware('verify.api.signature')->group(function () {
    //This route is testing purpose only,if you want to delete, you can
    Route::get('test/microservice',function (){
       return response()->json(['status'=>200,'msg'=>'Connection successful']);
    });
});
