<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\StripeController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/stripe/webhook', [StripeController::class, 'handle'])->withoutMiddleware(ValidateCsrfToken::class);;
