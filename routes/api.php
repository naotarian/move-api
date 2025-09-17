<?php

use App\Http\Controllers\Api\LuggageController;
use App\Http\Controllers\Api\Portal\EstimateController as PortalEstimateController;
use App\Http\Controllers\Api\Store\EstimateController as StoreEstimateController;
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
});
