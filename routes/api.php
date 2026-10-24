<?php

use App\Http\Controllers\Parent\ReportController;
use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\GradeController;
use App\Http\Controllers\Site\MonthCatalogController;
use App\Http\Controllers\Student\LibraryController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
    ]);
});

Route::get('/grades', [GradeController::class, 'index']);
Route::get('/grades/{grade:slug}', [GradeController::class, 'show']);
Route::get('/months', [MonthCatalogController::class, 'index']);
Route::get('/months/{month}', [MonthCatalogController::class, 'show']);
Route::post('/months/{month}/requests', [MonthCatalogController::class, 'storeRequest'])->middleware('throttle:8,1');

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:8,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:8,1');
Route::post('/parent/report', [ReportController::class, 'show'])->middleware('throttle:6,1');

Route::get('/student/videos/{video}/stream', [LibraryController::class, 'stream'])
    ->middleware(['device', 'throttle:60,1']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    require __DIR__.'/api/student.php';

    Route::middleware('teacher')->prefix('teacher')->group(function () {
        require __DIR__.'/api/admin.php';
    });
});
