<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OrganizationAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('organization')->check()) {
            return response()->json([
                'success' => false,
                'message' => '認証が必要です。'
            ], 401);
        }

        $organization = Auth::guard('organization')->user();

        // 組織のステータス確認
        if ($organization->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'アカウントが無効です。'
            ], 403);
        }

        // 認証済みかチェック（必要に応じて）
        if (!$organization->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'アカウントが認証されていません。'
            ], 403);
        }

        return $next($request);
    }
}
