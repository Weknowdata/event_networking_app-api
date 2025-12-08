<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\SessionAttendanceController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\LogoutController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/check', LoginController::class);
Route::post('/auth/google/mobile', [GoogleAuthController::class, 'mobile']);
Route::post('/signup', SignupController::class);
Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');

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

    // Challenges: today + history.
    Route::get('/challenges/today', [ChallengeController::class, 'today']);
    Route::get('/challenges/history', [ChallengeController::class, 'history']);

    // QR scan ingestion for sessions and sponsor booths.
    Route::post('/scan/session', [ScanController::class, 'session']);
    Route::post('/scan/session/checkout', [ScanController::class, 'checkout']);
    Route::post('/scan/booth', [ScanController::class, 'booth']);

    // Agenda: list and regenerate 5 or 7 day schedules with 9am-5pm slots.
    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::post('/agenda/generate', [AgendaController::class, 'generate']);

    // Session attendance history for the current user.
    Route::get('/sessions/attendance', [SessionAttendanceController::class, 'index']);

    // User feedback: create, fetch, and update.
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::get('/feedback/{user}', [FeedbackController::class, 'show']);
    Route::patch('/feedback/{feedback}', [FeedbackController::class, 'update']);
});
