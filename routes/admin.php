<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/admin/login', '/login?as=teacher');
Route::view('/admin', 'admin.dashboard', ['page' => 'dashboard']);
Route::view('/admin/students', 'admin.students', ['page' => 'students']);
Route::get('/admin/students/{student}', function (string $student) {
    return view('admin.student', ['page' => 'students', 'student' => $student]);
});
Route::view('/admin/years', 'admin.years', ['page' => 'years']);
Route::view('/admin/months', 'admin.months', ['page' => 'months']);
Route::get('/admin/months/{month}', function (string $month) {
    return view('admin.month', ['page' => 'months', 'month' => $month]);
});
Route::view('/admin/requests', 'admin.requests', ['page' => 'requests']);
Route::view('/admin/courses', 'admin.courses', ['page' => 'courses']);
Route::view('/admin/codes', 'admin.codes', ['page' => 'codes']);
Route::view('/admin/activations', 'admin.activations', ['page' => 'activations']);
Route::view('/admin/banks', 'admin.banks', ['page' => 'banks']);
Route::get('/admin/banks/{grade}/units/{unit}/lessons/{lesson}', function (string $grade, string $unit, string $lesson) {
    return view('admin.bank-board', ['page' => 'banks', 'board' => ['mode' => 'topics', 'grade' => $grade, 'unit' => $unit, 'lesson' => $lesson]]);
});
Route::get('/admin/banks/{grade}/units/{unit}', function (string $grade, string $unit) {
    return view('admin.bank-board', ['page' => 'banks', 'board' => ['mode' => 'lessons', 'grade' => $grade, 'unit' => $unit, 'lesson' => null]]);
});
Route::get('/admin/banks/{grade}/units', function (string $grade) {
    return view('admin.bank-board', ['page' => 'banks', 'board' => ['mode' => 'units', 'grade' => $grade, 'unit' => null, 'lesson' => null]]);
});
Route::get('/admin/banks/{grade}', function (string $grade) {
    return view('admin.bank', ['page' => 'banks', 'grade' => $grade]);
});
Route::get('/admin/courses/{course}', function (string $course) {
    return view('admin.course', ['page' => 'courses', 'course' => $course]);
});
Route::view('/admin/exams', 'admin.exams', ['page' => 'exams']);
Route::get('/admin/exams/{exam}', function (string $exam) {
    return view('admin.exam', ['page' => 'exams', 'exam' => $exam]);
});
