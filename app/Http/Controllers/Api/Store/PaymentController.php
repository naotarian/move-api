<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
// service
use App\Services\Store\Payment\StoreService as service;
use App\Services\Store\EstimateBidRight\StoreService as estimateBidRightService;

class PaymentController extends Controller
{
    private service $storeService;
    private estimateBidRightService $estimateBidRightService;
    public function __construct(service $storeService, estimateBidRightService $estimateBidRightService)
    {
        // Stripeのシークレットキーを設定
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->storeService = $storeService;
        $this->estimateBidRightService = $estimateBidRightService;
    }

    public function store(Request $request, $estimateId, $storeId)
    {
        try {
            $sessionId = $request->query('session_id');
            \Log::info('sessionId', ['sessionId' => $sessionId]);

            if (!$sessionId) {
                // セッションIDがない場合の失敗記録
                $paymentData = [
                    'estimate_id' => $estimateId,
                    'store_id' => $storeId,
                    'amount_excluding_tax' => 0,
                    'amount_including_tax' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'payment_date' => now(),
                    'payment_method' => 1, // クレジットカード
                    'status' => 3, // 失敗
                    'provider' => 'stripe',
                    'provider_id' => 'unknown',
                    'provider_url' => null,
                    'failure_reason' => 'セッションIDが見つかりません',
                ];
                $this->storeService->createPayment($paymentData);

                return redirect()->to(config('app.store_url') . '/store/estimates/' . $estimateId . '/bid?payment=error&message=session_not_found');
            }

            // Stripe APIで直接セッション情報を取得
            $session = Session::retrieve($sessionId);

            if (!$session) {
                // 無効なセッションの場合の失敗記録
                $paymentData = [
                    'estimate_id' => $estimateId,
                    'store_id' => $storeId,
                    'amount_excluding_tax' => 0,
                    'amount_including_tax' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'payment_date' => now(),
                    'payment_method' => 1, // クレジットカード
                    'status' => 3, // 失敗
                    'provider' => 'stripe',
                    'provider_id' => $sessionId,
                    'provider_url' => null,
                    'failure_reason' => '無効なセッションIDです',
                ];
                $this->storeService->createPayment($paymentData);

                return redirect()->to(config('app.store_url') . '/store/estimates/' . $estimateId . '/bid?payment=error&message=invalid_session');
            }



            // Payment Intentを取得（決済情報の詳細）
            $paymentIntent = null;
            if ($session->payment_intent) {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
            }

            // 決済が成功している場合のみ処理
            if ($session->payment_status === 'paid') {
                // 支払いデータを作成
                $paymentData = [
                    'estimate_id' => $estimateId,
                    'store_id' => $storeId,
                    'amount_excluding_tax' => $session->amount_subtotal ?? $session->amount_total,
                    'amount_including_tax' => $session->amount_total,
                    'tax_amount' => ($session->amount_total - ($session->amount_subtotal ?? $session->amount_total)),
                    'tax_rate' => $session->total_details->amount_tax ?? 0,
                    'payment_date' => now(),
                    'payment_method' => 1, // クレジットカード
                    'status' => 1, // 完了
                    'provider' => 'stripe',
                    'provider_id' => $session->id,
                    'provider_url' => $session->url,
                    'failure_reason' => null,
                ];

                $payment = $this->storeService->createPayment($paymentData);
                $this->estimateBidRightService->createEstimateBidRight($estimateId, $storeId, $payment->id);
                return redirect()->to(config('app.store_url') . '/store/estimates/' . $estimateId . '/bid?payment=success');
            } else {
                // 決済が失敗した場合のデータ作成
                $paymentData = [
                    'estimate_id' => $estimateId,
                    'store_id' => $storeId,
                    'amount_excluding_tax' => $session->amount_subtotal ?? $session->amount_total,
                    'amount_including_tax' => $session->amount_total,
                    'tax_amount' => ($session->amount_total - ($session->amount_subtotal ?? $session->amount_total)),
                    'tax_rate' => $session->total_details->amount_tax ?? 0,
                    'payment_date' => now(),
                    'payment_method' => 1, // クレジットカード
                    'status' => 3, // 失敗
                    'provider' => 'stripe',
                    'provider_id' => $session->id,
                    'provider_url' => $session->url,
                    'failure_reason' => '決済が完了していません。ステータス: ' . $session->payment_status,
                ];

                \Log::warning('Payment Not Completed - Creating failed payment record', [
                    'session_id' => $session->id,
                    'payment_status' => $session->payment_status,
                    'estimate_id' => $estimateId,
                    'store_id' => $storeId,
                    'payment_data' => $paymentData
                ]);

                $this->storeService->createPayment($paymentData);
                return redirect()->to(config('app.store_url') . '/store/estimates/' . $estimateId . '/bid?payment=error&message=payment_not_completed');
            }
        } catch (\Exception $e) {
            // 例外発生時の失敗記録を作成
            try {
                $paymentData = [
                    'estimate_id' => $estimateId,
                    'store_id' => $storeId,
                    'amount_excluding_tax' => 0,
                    'amount_including_tax' => 0,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'payment_date' => now(),
                    'payment_method' => 1, // クレジットカード
                    'status' => 3, // 失敗
                    'provider' => 'stripe',
                    'provider_id' => $request->query('session_id') ?? 'unknown',
                    'provider_url' => null,
                    'failure_reason' => 'システムエラー: ' . $e->getMessage(),
                ];

                $this->storeService->createPayment($paymentData);
            } catch (\Exception $createException) {
                \Log::error('Failed to create payment record for exception', [
                    'original_error' => $e->getMessage(),
                    'create_error' => $createException->getMessage()
                ]);
            }

            \Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'estimate_id' => $estimateId,
                'store_id' => $storeId,
                'session_id' => $request->query('session_id')
            ]);

            return redirect()->to(config('app.store_url') . '/store/estimates/' . $estimateId . '/bid?payment=error&message=' . urlencode($e->getMessage()));
        }
    }
}
