<?php

use App\Http\Controllers\Student\AccessController;
use App\Http\Controllers\Student\ExamController;
use App\Http\Controllers\Student\LibraryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['student', 'device'])->group(function () {
Route::get('/student/board', [AccessController::class, 'board']);
Route::post('/student/codes', [AccessController::class, 'redeem'])->middleware('throttle:8,1');
Route::get('/student/courses/{course}/lessons', [AccessController::class, 'lessons']);
Route::get('/student/lessons/{lesson}', [AccessController::class, 'showLesson']);
Route::post('/student/lessons/{lesson}/complete', [AccessController::class, 'completeLesson']);
Route::get('/student/months', [LibraryController::class, 'index']);
Route::post('/student/videos/{video}/ticket', [LibraryController::class, 'ticket'])->middleware('throttle:30,1');
Route::get('/student/videos/{video}/files/{file}', [LibraryController::class, 'file'])->middleware('throttle:30,1');
Route::get('/student/exams', [ExamController::class, 'index']);
Route::get('/student/exams/{exam}', [ExamController::class, 'show']);
Route::post('/student/exams/{exam}/start', [ExamController::class, 'start'])->middleware('throttle:20,1');
Route::post('/student/exams/{exam}/save', [ExamController::class, 'save'])->middleware('throttle:60,1');
Route::post('/student/exams/{exam}/submit', [ExamController::class, 'submit'])->middleware('throttle:20,1');
Route::post('/student/exams/{exam}/tab', [ExamController::class, 'tab'])->middleware('throttle:30,1');
});
