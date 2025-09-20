<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Exception;

class StripeController extends Controller
{
    public function __construct()
    {
        // Stripeのシークレットキーを設定
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Stripe Checkout セッションを作成
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'estimate_id' => 'required|string',
                'store_id' => 'required|string',
                'price' => 'required|integer|min:1',
                'success_url' => 'required|url',
                'cancel_url' => 'required|url',
                'product_name' => 'required|string',
                'product_description' => 'required|string',
            ]);

            // 見積もりの購入期限をチェック
            $estimate = \App\Models\Estimate::find($request->estimate_id);

            if (!$estimate) {
                return response()->json([
                    'success' => false,
                    'error' => '見積もりが見つかりません'
                ], 404);
            }

            if ($estimate->isPurchaseDeadlineExpired()) {
                \Log::warning('入札権購入期限切れ', [
                    'estimate_id' => $request->estimate_id,
                    'store_id' => $request->store_id,
                    'bid_deadline' => $estimate->bid_deadline?->toDateTimeString(),
                    'purchase_deadline' => $estimate->getBidRightPurchaseDeadline()?->toDateTimeString(),
                    'current_time' => now()->toDateTimeString()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'この見積もりの入札権購入期限が過ぎています（入札期限の15分前まで購入可能）',
                    'error_code' => 'PURCHASE_DEADLINE_EXPIRED'
                ], 400);
            }

            if (!$estimate->isWithinPurchaseDeadline()) {
                return response()->json([
                    'success' => false,
                    'error' => '現在この見積もりの入札権を購入することはできません',
                    'error_code' => 'PURCHASE_NOT_AVAILABLE'
                ], 400);
            }

            // Stripe Checkout セッションを作成
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $request->product_name,
                            'description' => $request->product_description,
                        ],
                        'unit_amount' => $request->price,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $request->success_url . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'estimate_id' => $request->estimate_id,
                    'store_id' => $request->store_id,
                    'type' => 'bid_right_purchase',
                ],
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);
        } catch (Exception $e) {
            \Log::error('Stripe checkout session creation failed', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'チェックアウトセッションの作成に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stripe Webhook を処理
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );

            // イベントタイプに応じて処理
            switch ($event['type']) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($event['data']['object']);
                    break;
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;
                default:
                    \Log::info('Unhandled Stripe webhook event', ['type' => $event['type']]);
            }

            return response()->json(['success' => true]);
        } catch (\UnexpectedValueException $e) {
            \Log::error('Invalid Stripe webhook payload', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            \Log::error('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 400);
        } catch (Exception $e) {
            \Log::error('Stripe webhook processing failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * チェックアウトセッション完了時の処理
     */
    private function handleCheckoutSessionCompleted($session)
    {
        $metadata = $session['metadata'];

        if ($metadata['type'] === 'bid_right_purchase') {
            // 入札権購入の処理
            $this->processBidRightPurchase(
                $metadata['estimate_id'],
                $metadata['store_id'],
                $session['id'],
                $session['amount_total']
            );
        }
    }

    /**
     * 支払い成功時の処理
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        \Log::info('Payment succeeded', ['payment_intent' => $paymentIntent['id']]);
    }

    /**
     * 入札権購入処理
     */
    private function processBidRightPurchase($estimateId, $storeId, $sessionId, $amount)
    {
        try {
            // EstimateBidRightテーブルに入札権を作成
            \DB::table('estimate_bid_rights')->insert([
                'id' => \Str::ulid(),
                'estimate_id' => $estimateId,
                'store_id' => $storeId,
                'status' => 1, // 有効
                'payment_id' => null, // 後でPaymentsテーブルと連携
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Paymentsテーブルに決済情報を記録
            \DB::table('payments')->insert([
                'id' => \Str::ulid(),
                'estimate_id' => $estimateId,
                'store_id' => $storeId,
                'amount_excluding_tax' => $amount,
                'amount_including_tax' => $amount,
                'tax_amount' => 0,
                'tax_rate' => 0,
                'payment_date' => now(),
                'payment_method' => 1, // クレジットカード
                'status' => 1, // 成功
                'provider' => 'stripe',
                'provider_id' => $sessionId,
                'provider_url' => null,
                'failure_reason' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Bid right purchase processed successfully', [
                'estimate_id' => $estimateId,
                'store_id' => $storeId,
                'session_id' => $sessionId
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to process bid right purchase', [
                'error' => $e->getMessage(),
                'estimate_id' => $estimateId,
                'store_id' => $storeId
            ]);
        }
    }

    /**
     * 決済成功後の処理（success_url用）
     */
    public function handleSuccess(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->query('session_id');

            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'error' => 'セッションIDが見つかりません'
                ], 400);
            }

            // Stripe APIでセッション情報を取得
            $session = Session::retrieve($sessionId);

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'error' => '無効なセッションIDです'
                ], 400);
            }

            // Payment Intentを取得（決済情報の詳細）
            $paymentIntent = null;
            if ($session->payment_intent) {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($session->payment_intent);
            }

            // セッション全体をログに出力
            \Log::info('Full Stripe Session Object (StripeController)', [
                'session_raw' => $session->toArray(),
            ]);

            // sessionの中身をログに出力
            \Log::info('Stripe Session Details', [
                'session_id' => $session->id,
                'payment_status' => $session->payment_status,
                'amount_total' => $session->amount_total,
                'currency' => $session->currency,
                'customer' => $session->customer,
                'customer_email' => $session->customer_details?->email,
                'payment_method_types' => $session->payment_method_types,
                'mode' => $session->mode,
                'status' => $session->status,
                'metadata' => $session->metadata?->toArray() ?? [],
                'created' => date('Y-m-d H:i:s', $session->created),
                'expires_at' => $session->expires_at ? date('Y-m-d H:i:s', $session->expires_at) : null,
                'payment_intent' => $session->payment_intent,
                'success_url' => $session->success_url,
                'cancel_url' => $session->cancel_url,
            ]);

            return response()->json([
                'success' => true,
                'session' => [
                    'id' => $session->id,
                    'payment_status' => $session->payment_status,
                    'amount_total' => $session->amount_total,
                    'currency' => $session->currency,
                    'metadata' => $session->metadata,
                ],
                'payment_intent' => $paymentIntent ? [
                    'id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                    'amount' => $paymentIntent->amount,
                    'charges' => $paymentIntent->charges->data,
                ] : null,
            ]);
        } catch (Exception $e) {
            \Log::error('Stripe success handling failed', [
                'error' => $e->getMessage(),
                'session_id' => $request->query('session_id')
            ]);

            return response()->json([
                'success' => false,
                'error' => '決済情報の取得に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }
}
