<?php

use App\Http\Controllers\Site\MonthCatalogController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'site.home');
Route::view('/login', 'site.auth', ['mode' => 'login']);
Route::view('/signup', 'site.auth', ['mode' => 'signup']);

Route::get('/grades/{grade}', function (string $grade) {
    return view('site.grade', ['slug' => $grade]);
});

Route::view('/months', 'site.months');
Route::get('/months/{month}/cover', [MonthCatalogController::class, 'cover']);
Route::get('/months/{month}', function (string $month) {
    return view('site.month', ['month' => $month]);
});

require __DIR__.'/admin.php';
require __DIR__.'/student.php';
require __DIR__.'/parent.php';
