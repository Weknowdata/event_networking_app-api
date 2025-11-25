<?php

use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\LeaderboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/check', LoginController::class);
Route::post('/auth/google/mobile', [GoogleAuthController::class, 'mobile']);
Route::post('/signup', SignupController::class);

Route::middleware('auth:sanctum')->group(function () {
    // Authenticated user info/profile.
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::patch('/user/profile', [UserProfileController::class, 'update']);

    // Attendee discovery (excludes current user, supports search/filter).
    Route::get('/attendees', [AttendeeController::class, 'index']);

    // Connection lifecycle: list connections, create a new one, and add notes.
    Route::get('/connections', [ConnectionController::class, 'index']);
    Route::post('/connections', [ConnectionController::class, 'store']);
    Route::patch('/connections/{connection}/notes', [ConnectionController::class, 'updateNotes']);

    // Points leaderboard (supports period/limit, returns viewer rank).
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
});
