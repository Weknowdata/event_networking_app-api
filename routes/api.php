<?php

use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/check', LoginController::class);
Route::post('/auth/google/mobile', [GoogleAuthController::class, 'mobile']);
Route::post('/signup', SignupController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::patch('/user/profile', [UserProfileController::class, 'update']);
});
