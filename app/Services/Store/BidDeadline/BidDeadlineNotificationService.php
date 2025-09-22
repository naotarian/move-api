<?php

namespace App\Services\Store\BidDeadline;

use App\Services\EstimateMailService;
use App\Repositories\Store\BidDeadline\BidDeadlineRepositoryInterface;
use App\Models\BusinessRight;
use App\Mail\BusinessRightGrantedMail;
use App\Mail\CustomerNotificationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BidDeadlineNotificationService
{
    public function __construct(
        private EstimateMailService $estimateMailService,
        private BidDeadlineRepositoryInterface $bidDeadlineRepository
    ) {}

    /**
     * 営業権獲得店舗への通知を送信
     */
    public function sendBusinessRightNotifications(array $businessRightsByEstimate): array
    {
        Log::info('BidDeadlineNotificationService: Starting business right notifications', [
            'estimates_count' => count($businessRightsByEstimate)
        ]);

        $results = [
            'sent_to_stores' => 0,
            'sent_to_customers' => 0,
            'failed_notifications' => 0
        ];

        foreach ($businessRightsByEstimate as $estimateId => $data) {
            $this->sendNotificationsForEstimate($data, $results);
        }

        Log::info('BidDeadlineNotificationService: Completed business right notifications', $results);

        return $results;
    }

    /**
     * 見積もり単位での通知送信
     */
    private function sendNotificationsForEstimate(array $data, array &$results): void
    {
        $estimate = $data['estimate'];
        $rights = $data['rights'];

        Log::info('BidDeadlineNotificationService: Sending notifications for estimate', [
            'estimate_id' => $estimate->id,
            'rights_count' => count($rights)
        ]);

        try {
            // 営業権獲得店舗への通知
            foreach ($rights as $right) {
                $this->sendStoreNotification($right, $results);
            }

            // 顧客への通知
            $this->sendCustomerNotification($estimate, $rights, $results);
        } catch (\Exception $e) {
            Log::error('BidDeadlineNotificationService: Failed to send notifications for estimate', [
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage()
            ]);
            $results['failed_notifications']++;
        }
    }

    /**
     * 店舗への営業権獲得通知
     */
    private function sendStoreNotification(array $right, array &$results): void
    {
        try {
            // BusinessRightモデルを取得（既に作成済み）
            $businessRight = BusinessRight::where('estimate_id', $right['estimate_id'])
                ->where('store_id', $right['store_id'])
                ->with(['estimate.movingFromAddress', 'estimate.movingToAddress', 'store'])
                ->first();

            if (!$businessRight) {
                throw new \Exception('BusinessRight not found');
            }

            // 同じ見積もりの全営業権獲得者を取得
            $allWinners = BusinessRight::where('estimate_id', $right['estimate_id'])
                ->with('store')
                ->orderBy('ranking')
                ->get()
                ->map(function ($br) {
                    return [
                        'store_id' => $br->store_id,
                        'store_name' => $br->store->name,
                        'ranking' => $br->ranking,
                        'bid_amount_min' => $br->bid_amount_min,
                        'bid_amount_max' => $br->bid_amount_max,
                    ];
                })->toArray();

            // メール送信
            Mail::to($businessRight->store->email)
                ->send(new BusinessRightGrantedMail(
                    $businessRight->store,
                    $businessRight->estimate,
                    $businessRight,
                    $allWinners
                ));

            Log::info('📧 店舗向けメール送信完了', [
                'store_id' => $right['store_id'],
                'store_name' => $businessRight->store->name,
                'store_email' => $businessRight->store->email,
                'estimate_id' => $right['estimate_id'],
                'ranking' => $right['ranking']
            ]);

            $results['sent_to_stores']++;

            // 通知完了をマーク
            $businessRight->markAsNotified();
        } catch (\Exception $e) {
            Log::error('❌ 店舗向けメール送信失敗', [
                'store_id' => $right['store_id'],
                'estimate_id' => $right['estimate_id'],
                'error' => $e->getMessage()
            ]);
            $results['failed_notifications']++;
        }
    }

    /**
     * 顧客への営業権獲得業者通知
     */
    private function sendCustomerNotification($estimate, array $rights, array &$results): void
    {
        try {
            // 営業権データを整形
            $businessRightsData = array_map(function ($right) {
                return [
                    'store_name' => $right['store_name'] ?? '店舗名不明',
                    'ranking' => $right['ranking'],
                    'bid_amount_min' => $right['bid_amount_min'],
                    'bid_amount_max' => $right['bid_amount_max']
                ];
            }, $rights);

            // 顧客にメール送信
            Mail::to($estimate->email)
                ->send(new CustomerNotificationMail(
                    $estimate,
                    $businessRightsData
                ));

            Log::info('📧 顧客向けメール送信完了', [
                'customer_email' => $estimate->email,
                'customer_name' => $estimate->name,
                'estimate_id' => $estimate->id,
                'winning_stores_count' => count($businessRightsData)
            ]);

            $results['sent_to_customers']++;
        } catch (\Exception $e) {
            Log::error('❌ 顧客向けメール送信失敗', [
                'customer_email' => $estimate->email,
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage()
            ]);
            $results['failed_notifications']++;
        }
    }
}
