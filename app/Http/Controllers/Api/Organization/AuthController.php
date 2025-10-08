<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\LoginRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * 組織ログイン
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $credentials = $request->validated();

            Log::info('Organization login attempt', [
                'email' => $credentials['email']
            ]);

            // 組織を検索
            $organization = Organization::where('email', $credentials['email'])->first();

            if (!$organization) {
                Log::warning('Organization not found', [
                    'email' => $credentials['email']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'メールアドレスまたはパスワードが正しくありません。'
                ], 401);
            }

            // パスワード確認
            if (!Hash::check($credentials['password'], $organization->password)) {
                Log::warning('Organization password mismatch', [
                    'organization_id' => $organization->id,
                    'email' => $credentials['email']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'メールアドレスまたはパスワードが正しくありません。'
                ], 401);
            }

            // 組織のステータス確認
            if (!$organization->isActive()) {
                Log::warning('Inactive organization login attempt', [
                    'organization_id' => $organization->id,
                    'status' => $organization->status
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'アカウントが無効です。管理者にお問い合わせください。'
                ], 403);
            }

            // 認証済みかチェック（必要に応じて）
            if (!$organization->isVerified()) {
                Log::warning('Unverified organization login attempt', [
                    'organization_id' => $organization->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'アカウントが認証されていません。管理者にお問い合わせください。'
                ], 403);
            }

            // トークン生成
            $token = $organization->createToken('organization_token')->plainTextToken;

            // 最終ログイン日時を更新
            $organization->update([
                'last_login_at' => now()
            ]);

            Log::info('Organization login successful', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name
            ]);
            \Log::info($token);

            return response()->json([
                'success' => true,
                'message' => 'ログインに成功しました。',
                'data' => [
                    'organization' => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                        'email' => $organization->email,
                        'phone' => $organization->phone,
                        'address' => $organization->address,
                        'status' => $organization->status,
                        'is_verified' => $organization->is_verified,
                        'store_count' => $organization->stores->count(),
                        'active_store_count' => $organization->stores->where('status', 'active')->count(),
                    ],
                    'token' => $token,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Organization login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ログインに失敗しました。しばらく時間をおいて再度お試しください。'
            ], 500);
        }
    }

    /**
     * トークン検証
     */
    public function verify(Request $request): JsonResponse
    {
        $organization = Auth::guard('organization')->user();

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => '認証に失敗しました',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => '認証に成功しました',
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'email' => $organization->email,
                'status' => $organization->status,
                'is_verified' => $organization->is_verified,
            ],
        ]);
    }

    /**
     * 組織ログアウト
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $organization = Auth::guard('organization')->user();

            if ($organization) {
                // 現在のトークンを削除
                $request->user()->currentAccessToken()->delete();

                Log::info('Organization logout successful', [
                    'organization_id' => $organization->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'ログアウトしました。'
            ]);
        } catch (\Exception $e) {
            Log::error('Organization logout error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ログアウトに失敗しました。'
            ], 500);
        }
    }

    /**
     * 認証済み組織情報取得
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $organization = Auth::guard('organization')->user();

            if (!$organization) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です。'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'organization' => [
                        'id' => $organization->id,
                        'name' => $organization->name,
                        'email' => $organization->email,
                        'phone' => $organization->phone,
                        'address' => $organization->address,
                        'status' => $organization->status,
                        'is_verified' => $organization->is_verified,
                        'store_count' => $organization->stores->count(),
                        'active_store_count' => $organization->stores->where('status', 'active')->count(),
                        'last_login_at' => $organization->last_login_at,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Organization me error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ユーザー情報の取得に失敗しました。'
            ], 500);
        }
    }
}
