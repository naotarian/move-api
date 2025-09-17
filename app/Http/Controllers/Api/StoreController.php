<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    /**
     * 店舗ログイン
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました',
                'errors' => $validator->errors(),
            ], 422);
        }

        $store = Store::where('email', $request->email)
            ->where('status', Store::STATUS_ACTIVE)
            ->first();

        if (!$store || !Hash::check($request->password, $store->password)) {
            return response()->json([
                'success' => false,
                'message' => 'メールアドレスまたはパスワードが正しくありません',
            ], 401);
        }

        // 最終ログイン日時を更新
        $store->update(['last_login_at' => now()]);

        // トークンを作成
        $token = $store->createToken('store-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ログインに成功しました',
            'token' => $token,
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email,
                'status' => $store->status,
                'is_verified' => $store->is_verified,
            ],
        ]);
    }

    /**
     * トークン検証
     */
    public function verify(Request $request): JsonResponse
    {
        $store = Auth::guard('store')->user();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => '認証に失敗しました',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => '認証に成功しました',
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'email' => $store->email,
                'status' => $store->status,
                'is_verified' => $store->is_verified,
            ],
        ]);
    }

    /**
     * ログアウト
     */
    public function logout(Request $request): JsonResponse
    {
        $store = Auth::guard('store')->user();
        
        if ($store) {
            // 現在のトークンを削除
            $store->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました',
        ]);
    }

    /**
     * 店舗一覧（管理者のみ）
     */
    public function index(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '権限がありません',
            ], 403);
        }

        $stores = Store::select(['id', 'name', 'email', 'phone', 'address', 'license_number', 'status', 'is_verified', 'last_login_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $stores,
        ]);
    }

    /**
     * 店舗作成（管理者のみ）
     */
    public function store(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '権限がありません',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:stores,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'license_number' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newStore = Store::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'license_number' => $request->license_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => '店舗を作成しました',
            'data' => [
                'id' => $newStore->id,
                'name' => $newStore->name,
                'email' => $newStore->email,
                'status' => $newStore->status,
            ],
        ], 201);
    }
}
