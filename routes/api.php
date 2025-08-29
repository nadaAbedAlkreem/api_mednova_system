<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Dashborad\OrderController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function ()
{
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login'])->name('login-post');
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/order/store', [OrderController::class, 'store']);
});
