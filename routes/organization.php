<?php

use App\Http\Controllers\Api\Organization\AuthController as OrganizationAuthController;
use App\Http\Controllers\Api\Organization\StoreController;
use App\Http\Controllers\Api\StripeController;
use App\Http\Controllers\Api\Organization\PaymentInformationController;
use App\Http\Controllers\Api\Organization\PaymentSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Organization API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for organization functionality.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group and prefixed with "organization".
|
*/

// 認証不要
Route::post('/login', [OrganizationAuthController::class, 'login']); // ログイン

// 認証必要
Route::middleware('auth:organization')->group(function () {
    Route::post('/logout', [OrganizationAuthController::class, 'logout']); // ログアウト
    Route::get('/me', [OrganizationAuthController::class, 'me']); // 認証済み組織情報取得
    Route::get('/verify', [OrganizationAuthController::class, 'verify']); // トークン検証
    Route::prefix('stores')->group(function () {
        Route::get('/', [StoreController::class, 'index']); // 店舗一覧取得
        Route::get('/{id}', [StoreController::class, 'show']); // 店舗詳細取得
        Route::put('/payment-method/{id}', [StoreController::class, 'updatePaymentMethod']);
    });
    Route::prefix('stripe')->group(function () {
        Route::post('/setup-intent', [StripeController::class, 'setupIntent']); // セットアップインテント作成
    });

    Route::prefix('payment-information')->group(function () {
        Route::get('/', [PaymentInformationController::class, 'index']); // 支払い情報一覧取得
        Route::get('/setting', [PaymentSettingController::class, 'index']); // 支払い設定取得
    });
});
