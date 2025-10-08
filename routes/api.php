<?php

use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\user\LocationController;
use App\Http\Controllers\Api\user\PatientController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function ()
{
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/login', [LoginController::class, 'login'])->name('login-post');
    Route::post('social-callback', [SocialAuthController::class, 'handleSocialLogin']);
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);
    Route::post('verifyToken', [ForgotPasswordController::class, 'verifyToken']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::prefix('patient')->group(function ()
    {
        Route::post('/store', [PatientController::class, 'store']);

    });
    Route::prefix('location')->group(function ()
    {
        Route::post('store', [LocationController::class, 'store']);
        Route::post('update', [LocationController::class, 'update']);
    });
});
