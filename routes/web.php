<?php


use App\Http\Controllers\Api\Payment\WalletTopUpController;
use Illuminate\Support\Facades\Route;




Route::post('/amwalpay/callback', [WalletTopUpController::class, 'captureDataViaWebhook']);
