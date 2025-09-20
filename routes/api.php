<?php

use App\Http\Controllers\Api\LuggageController;
// Portal
use App\Http\Controllers\Api\Portal\EstimateController as PortalEstimateController;
// Store
use App\Http\Controllers\Api\Store\EstimateController as StoreEstimateController;
use App\Http\Controllers\Api\Store\PaymentController as StorePaymentController;
use App\Http\Controllers\Api\Store\EstimateBidRightController as StoreEstimateBidRightController;
use App\Http\Controllers\Api\Store\StripeController as StoreStripeController;
use App\Http\Controllers\Api\Store\BidController as StoreBidController;
use App\Http\Controllers\Api\Store\PurchaseHistoryController as StorePurchaseHistoryController;
// Admin
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 荷物マスタ関連のAPI
Route::prefix('luggage')->group(function () {
    Route::get('/categories', [LuggageController::class, 'categories']);
    Route::get('/master', [LuggageController::class, 'master']);
    Route::get('/combined', [LuggageController::class, 'combined']);
    Route::get('/category/{categoryCode}', [LuggageController::class, 'byCategory']);
});

// 見積もり関連のAPI
Route::prefix('estimate')->group(function () {
    Route::post('/', [PortalEstimateController::class, 'store']); // 見積もり作成（フロントエンド用）
    Route::get('/', [StoreEstimateController::class, 'index'])->middleware('auth:store'); // 見積もり一覧（店舗用）-
    Route::get('/{id}', [StoreEstimateController::class, 'show'])->middleware('auth:store'); // 見積もり詳細（店舗用）-
});

// 認証関連のAPI（フロントエンド用）
Route::prefix('verification')->group(function () {
    Route::post('/resend-email', [PortalEstimateController::class, 'resendEmailVerification']); // メール認証再送
    Route::get('/verify-email', [PortalEstimateController::class, 'verifyEmail']); // メール認証確認
    Route::get('/sms-status', [PortalEstimateController::class, 'getSmsStatus']); // SMS送信状況取得
    Route::post('/resend-sms', [PortalEstimateController::class, 'resendSmsVerification']); // SMS認証再送
    Route::post('/verify-sms', [PortalEstimateController::class, 'verifySms']); // SMS認証確認
});

// 管理者認証関連のAPI
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']); // ログイン
    Route::post('/logout', [AdminController::class, 'logout'])->middleware('auth:admin'); // ログアウト
    Route::get('/verify', [AdminController::class, 'verify'])->middleware('auth:admin'); // トークン検証
    Route::get('/', [AdminController::class, 'index'])->middleware('auth:admin'); // 管理者一覧
    Route::post('/', [AdminController::class, 'store'])->middleware('auth:admin'); // 管理者作成
});

// 店舗認証関連のAPI
Route::prefix('store')->group(function () {
    Route::post('/login', [StoreController::class, 'login']); // ログイン
    Route::post('/logout', [StoreController::class, 'logout'])->middleware('auth:store'); // ログアウト
    Route::get('/verify', [StoreController::class, 'verify'])->middleware('auth:store'); // トークン検証
    Route::get('/', [StoreController::class, 'index'])->middleware('auth:admin'); // 店舗一覧（管理者のみ）
    Route::post('/', [StoreController::class, 'store'])->middleware('auth:admin'); // 店舗作成（管理者のみ）

    Route::prefix('payment')->group(function () {
        Route::get('/{estimateId}/{storeId}', [StorePaymentController::class, 'store']); // 支払い作成
    });

    Route::prefix('estimate-bid-rights')->group(function () {
        Route::post('/', [StoreEstimateBidRightController::class, 'store'])->middleware('auth:store'); // 入札権作成
        Route::get('/', [StoreEstimateBidRightController::class, 'index'])->middleware('auth:store'); // 入札状況
        Route::post('/check', [StoreEstimateBidRightController::class, 'check'])->middleware('auth:store'); // 入札権確認
    });

    Route::prefix('estimates/{estimateId}/bids')->group(function () {
        Route::get('/', [StoreBidController::class, 'index'])->middleware('auth:store'); // 入札一覧取得
        Route::post('/', [StoreBidController::class, 'store'])->middleware('auth:store'); // 入札作成
        Route::get('/my', [StoreBidController::class, 'getMyBid'])->middleware('auth:store'); // 自分の入札取得
    });

    // 購入履歴関連のAPI
    Route::prefix('purchase-history')->group(function () {
        Route::get('/', [StorePurchaseHistoryController::class, 'index'])->middleware('auth:store'); // 購入履歴一覧
    });
});

// Stripe決済関連のAPI（Webhookは認証不要）
Route::prefix('stripe')->group(function () {
    Route::post('/create-checkout-session', [StoreStripeController::class, 'createCheckoutSession'])->middleware('auth:store');
    Route::get('/success', [StoreStripeController::class, 'handleSuccess']); // 決済成功後の処理
    Route::post('/webhook', [StoreStripeController::class, 'handleWebhook']); // Webhook用（認証不要）
});
