<?php

use Illuminate\Support\Facades\Route;

Route::view('/learn', 'student.index');
Route::view('/learn/profile', 'student.profile');
Route::view('/learn/months', 'student.months');
Route::get('/learn/months/{month}/learn', function (string $month) {
    return view('student.watch', ['month' => $month]);
});
Route::get('/learn/months/{month}', function (string $month) {
    return view('student.month', ['month' => $month]);
});
Route::view('/learn/exams', 'student.exams');
Route::get('/learn/exams/{exam}', function (string $exam) {
    return view('student.exam', ['exam' => $exam]);
});
