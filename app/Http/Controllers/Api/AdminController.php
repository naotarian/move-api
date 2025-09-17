<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * 管理者ログイン
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

        $admin = Admin::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'メールアドレスまたはパスワードが正しくありません',
            ], 401);
        }

        // 最終ログイン日時を更新
        $admin->update(['last_login_at' => now()]);

        // トークンを作成
        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'ログインに成功しました',
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
        ]);
    }

    /**
     * トークン検証
     */
    public function verify(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => '認証に失敗しました',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => '認証に成功しました',
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role,
            ],
        ]);
    }

    /**
     * ログアウト
     */
    public function logout(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();
        
        if ($admin) {
            // 現在のトークンを削除
            $admin->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'ログアウトしました',
        ]);
    }

    /**
     * 管理者一覧（スーパー管理者のみ）
     */
    public function index(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '権限がありません',
            ], 403);
        }

        $admins = Admin::select(['id', 'name', 'email', 'role', 'is_active', 'last_login_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $admins,
        ]);
    }

    /**
     * 管理者作成（スーパー管理者のみ）
     */
    public function store(Request $request): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin || !$admin->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => '権限がありません',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:super_admin,admin,viewer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newAdmin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => '管理者を作成しました',
            'data' => [
                'id' => $newAdmin->id,
                'name' => $newAdmin->name,
                'email' => $newAdmin->email,
                'role' => $newAdmin->role,
            ],
        ], 201);
    }
}
