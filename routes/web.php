<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('auth')->group(function () {
    Route::get('redirect', [AuthController::class, 'redirect'])->name('login');
    Route::get('logout', [AuthController::class, 'redirect'])->name('logout');
    Route::get('callback', [AuthController::class, 'callback'])->name('auth.callback');
    Route::get('profile', [AuthController::class, 'profile'])->name('auth.profile');
});
