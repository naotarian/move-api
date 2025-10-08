<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\UseCases\Portal\Estimate\CreateEstimateUseCase;
use App\Services\Portal\Estimate\EmailVerificationService;
use App\Services\Portal\Estimate\SmsVerificationService;
use App\Models\Estimate;
use App\Models\EmailVerificationToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;

class EstimateController extends Controller
{
    public function __construct(
        private CreateEstimateUseCase $createEstimateUseCase,
        private EmailVerificationService $emailVerificationService,
        private SmsVerificationService $smsVerificationService
    ) {}

    /**
     * 見積もり作成（フロントエンド用）
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'name_furigana' => 'required|string|max:100',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'moving_date_type' => 'required|in:undecided,decided',
                'moving_specific_date' => 'nullable|date',
                'moving_year_month' => 'nullable|string',
                'moving_period' => 'nullable|in:early,middle,late',
                'people_count' => 'required|integer|min:1|max:4',
                'work_start_time_type' => 'required|in:anytime,specific',
                'work_start_time' => 'nullable|in:morning,afternoon,evening',
                'from_zipcode' => 'required|string|max:10',
                'from_prefecture' => 'required|string|max:100',
                'from_street_address' => 'required|string|max:255',
                'from_building_details' => 'nullable|string|max:255',
                'from_building_type' => 'required|string|max:50',
                'from_room_layout' => 'required|string|max:50',
                'from_floor' => 'required|string|max:20',
                'from_elevator' => 'required|in:yes,no',
                'to_zipcode' => 'required|string|max:10',
                'to_prefecture' => 'required|string|max:100',
                'to_street_address' => 'required|string|max:255',
                'to_building_details' => 'nullable|string|max:255',
                'to_building_type' => 'required|string|max:50',
                'to_room_layout' => 'required|string|max:50',
                'to_floor' => 'required|string|max:20',
                'to_elevator' => 'required|in:yes,no',
                'luggage_items' => 'array',
                'luggage_items.*.id' => 'required|string',
                'luggage_items.*.quantity' => 'required|integer|min:1',
                'other_luggage' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                \Log::error('EstimateController: Validation failed', [
                    'errors' => $validator->errors(),
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // UseCaseを実行
            \Log::info('EstimateController: Starting estimate creation', [
                'request' => $request->all()
            ]);
            $result = $this->createEstimateUseCase->execute($request->all());

            return response()->json($result, 201);
        } catch (\Exception $e) {
            \Log::error('見積もり作成エラー: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '見積もりの作成に失敗しました',
            ], 500);
        }
    }

    /**
     * メール認証再送
     */
    public function resendEmailVerification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estimate_id' => 'required|string|size:26', // ULID形式
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $estimateId = $request->input('estimate_id');

            \Log::info('EstimateController: Email resend requested', [
                'estimate_id' => $estimateId
            ]);

            // 見積もりの存在確認
            $estimate = Estimate::find($estimateId);
            if (!$estimate) {
                return response()->json([
                    'success' => false,
                    'message' => '見積もりが見つかりません',
                ], 404);
            }

            // 既に認証済みの場合
            if ($estimate->email_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'このメールアドレスは既に認証済みです',
                ], 400);
            }

            // メール認証を再送信
            $result = $this->emailVerificationService->sendEmailVerification($estimate);

            if ($result['success']) {
                \Log::info('EstimateController: Email resend successful', [
                    'estimate_id' => $estimateId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => '認証メールを再送しました',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'メール送信に失敗しました',
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('EstimateController: Email resend failed', [
                'estimate_id' => $request->input('estimate_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'メール再送中にエラーが発生しました',
            ], 500);
        }
    }

    /**
     * メール認証確認（メールリンクからの直接アクセス）
     */
    public function verifyEmail(Request $request): RedirectResponse
    {
        try {
            $token = $request->query('token');

            if (!$token) {
                \Log::warning('EstimateController: No token provided for email verification');
                return redirect(config('app.frontend_url') . '/verify-email-complete?error=no_token');
            }

            \Log::info('EstimateController: Email verification requested', [
                'token' => substr($token, 0, 10) . '...'
            ]);

            // 1. メール認証を実行
            $emailResult = $this->emailVerificationService->verifyEmailToken($token);

            if (!$emailResult['success']) {
                \Log::warning('EstimateController: Email verification failed', [
                    'error_code' => $emailResult['error_code'] ?? 'unknown',
                    'message' => $emailResult['message'] ?? 'Unknown error'
                ]);

                $errorType = match ($emailResult['error_code'] ?? '') {
                    'INVALID_TOKEN' => 'invalid_token',
                    'ESTIMATE_NOT_FOUND' => 'estimate_not_found',
                    default => 'verification_failed',
                };

                return redirect(config('app.frontend_url') . "/verify-email-complete?error={$errorType}");
            }

            $estimateId = $emailResult['estimate_id'];
            $alreadyVerified = $emailResult['already_verified'] ?? false;

            \Log::info('EstimateController: Email verification completed', [
                'estimate_id' => $estimateId,
                'already_verified' => $alreadyVerified
            ]);

            // 2. SMS認証コードを送信（既に認証済みでない場合のみ）
            $smsResult = ['success' => false, 'message' => 'SMS送信をスキップしました'];

            if (!$alreadyVerified) {
                // 見積もり情報を取得してSMS送信
                $estimate = Estimate::find($estimateId);
                if ($estimate && !$estimate->phone_verified) {
                    $smsResult = $this->smsVerificationService->sendSmsVerification($estimate);

                    \Log::info('EstimateController: SMS verification sent', [
                        'estimate_id' => $estimateId,
                        'sms_success' => $smsResult['success'] ?? false
                    ]);
                }
            }

            // 3. フロントエンドにリダイレクト（最小限の情報のみ）
            $queryParams = [
                'estimate_id' => $estimateId,
                'status' => 'success'
            ];

            // 既に認証済みの場合のみフラグを追加
            if ($alreadyVerified) {
                $queryParams['already_verified'] = 'true';
            }

            $redirectUrl = config('app.frontend_url') . '/verify-email-complete?' . http_build_query($queryParams);

            \Log::info('EstimateController: Redirecting to frontend', [
                'estimate_id' => $estimateId,
                'redirect_url' => $redirectUrl,
                'sms_sent' => $smsResult['success'] ?? false
            ]);

            return redirect($redirectUrl);
        } catch (\Exception $e) {
            \Log::error('EstimateController: Email verification failed', [
                'token' => substr($request->query('token', ''), 0, 10) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect(config('app.frontend_url') . '/verify-email-complete?error=server_error');
        }
    }

    /**
     * SMS送信状況を取得
     */
    public function getSmsStatus(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estimate_id' => 'required|string|size:26', // ULID形式
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $estimateId = $request->input('estimate_id');

            // 見積もりの存在確認
            $estimate = Estimate::find($estimateId);
            if (!$estimate) {
                return response()->json([
                    'success' => false,
                    'message' => '見積もりが見つかりません'
                ], 404);
            }

            // 最新のSMS認証コードを取得
            $latestSmsCode = $estimate->smsVerificationCodes()
                ->orderBy('created_at', 'desc')
                ->first();

            $smsStatus = [
                'phone_verified' => $estimate->phone_verified,
                'sms_sent' => false,
                'sms_message' => null,
                'sms_expires_at' => null
            ];

            if ($latestSmsCode) {
                $smsStatus['sms_sent'] = $latestSmsCode->send_result['success'] ?? false;
                $smsStatus['sms_message'] = $latestSmsCode->send_result['message'] ?? null;
                $smsStatus['sms_expires_at'] = $latestSmsCode->expires_at;

                // SMS送信失敗の場合は詳細なメッセージを提供
                if (!$smsStatus['sms_sent']) {
                    $smsStatus['sms_message'] = 'SMS認証コードの送信に失敗しました';
                } else {
                    $smsStatus['sms_message'] = 'SMS認証コードを送信しました';
                }
            }

            return response()->json([
                'success' => true,
                'data' => $smsStatus
            ]);
        } catch (\Exception $e) {
            \Log::error('EstimateController: SMS status retrieval failed', [
                'estimate_id' => $request->input('estimate_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMS状況の取得中にエラーが発生しました',
            ], 500);
        }
    }

    /**
     * SMS認証再送
     */
    public function resendSmsVerification(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estimate_id' => 'required|string|size:26', // ULID形式
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $estimateId = $request->input('estimate_id');

            \Log::info('EstimateController: SMS resend requested', [
                'estimate_id' => $estimateId
            ]);

            // 見積もりの存在確認
            $estimate = Estimate::find($estimateId);
            if (!$estimate) {
                return response()->json([
                    'success' => false,
                    'message' => '見積もりが見つかりません',
                ], 404);
            }

            // 既に認証済みの場合
            if ($estimate->phone_verified) {
                return response()->json([
                    'success' => false,
                    'message' => 'この電話番号は既に認証済みです',
                ], 400);
            }

            // SMS認証を再送信
            $result = $this->smsVerificationService->sendSmsVerification($estimate);

            if ($result['success']) {
                \Log::info('EstimateController: SMS resend successful', [
                    'estimate_id' => $estimateId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS認証コードを再送しました',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'SMS送信に失敗しました',
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('EstimateController: SMS resend failed', [
                'estimate_id' => $request->input('estimate_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMS再送中にエラーが発生しました',
            ], 500);
        }
    }

    /**
     * SMS認証確認
     */
    public function verifySms(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|size:6', // 6桁の数字
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $code = $request->input('code');

            \Log::info('EstimateController: SMS verification requested', [
                'code' => $code
            ]);

            // SMS認証を実行
            $result = $this->smsVerificationService->verifySmsCode($code);

            if ($result['success']) {
                \Log::info('EstimateController: SMS verification completed', [
                    'estimate_id' => $result['estimate_id'] ?? 'N/A',
                    'already_verified' => $result['already_verified'] ?? false
                ]);

                // 認証完了ページのURLを生成
                $completeUrl = config('app.frontend_url') . '/complete?estimate_id=' . ($result['estimate_id'] ?? '');

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'estimate_id' => $result['estimate_id'] ?? null,
                    'already_verified' => $result['already_verified'] ?? false,
                    'redirect_url' => $completeUrl,
                ]);
            } else {
                $statusCode = match ($result['error_code'] ?? '') {
                    'INVALID_CODE' => 400,
                    'ESTIMATE_NOT_FOUND' => 404,
                    default => 500,
                };

                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'error_code' => $result['error_code'] ?? null,
                ], $statusCode);
            }
        } catch (\Exception $e) {
            \Log::error('EstimateController: SMS verification failed', [
                'code' => $request->input('code'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'SMS認証中にエラーが発生しました',
            ], 500);
        }
    }
}
