<?php

use App\Http\Controllers\Dashboard\Auth\LoginController;
use App\Http\Controllers\Dashboard\order\OrderController;
use App\Http\Controllers\Dashboard\order\OrderNotificationController;
use Illuminate\Support\Facades\Route;


Route::prefix('admin')->group(callback: function () {

    Route::middleware(['guest'])->group(function () {
        Route::get('login', [LoginController::class , 'index'])->name('admin.login');
        Route::post('login', [LoginController::class , 'login'])->name('admin.login.store');
    });
    Route::middleware(['auth'])->group(function () {
        Route::get('/home', [OrderController::class , 'index'])->name('admin.dashboard.home');
        Route::get('/notification', [OrderNotificationController::class , 'index'])->name('admin.dashboard.notification');
        Route::post('/notification/add', [OrderNotificationController::class , 'store'])->name('admin.dashboard.notification.store');
        Route::get('/logout', [LoginController::class , 'logout'])->name('admin.logout');

    });


});



