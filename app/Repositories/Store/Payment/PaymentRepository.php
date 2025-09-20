<?php

namespace App\Repositories\Store\Payment;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentRepository implements PaymentRepositoryInterface
{
    /**
     * 支払い情報を作成
     */
    public function create(array $data): Payment
    {
        $payment = Payment::create([
            'estimate_id' => $data['estimate_id'],
            'store_id' => $data['store_id'],
            'amount_excluding_tax' => $data['amount_excluding_tax'] ?? 0,
            'amount_including_tax' => $data['amount_including_tax'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? 0,
            'payment_date' => $data['payment_date'] ?? now(),
            'payment_method' => $data['payment_method'] ?? Payment::PAYMENT_METHOD_CREDIT_CARD,
            'status' => $data['status'] ?? Payment::STATUS_SUCCESS,
            'provider' => $data['provider'] ?? null,
            'provider_id' => $data['provider_id'] ?? null,
            'provider_url' => $data['provider_url'] ?? null,
            'failure_reason' => $data['failure_reason'] ?? null,
        ]);

        return $payment;
    }

    /**
     * IDで支払い情報を取得
     */
    public function findById(string $id): ?Payment
    {
        Log::info('PaymentRepository findById', ['id' => $id]);

        $payment = Payment::find($id);

        Log::info('PaymentRepository findById result', [
            'id' => $id,
            'found' => $payment !== null
        ]);

        return $payment;
    }

    /**
     * 見積もりIDで支払い情報を取得
     */
    public function findByEstimateId(string $estimateId): array
    {
        Log::info('PaymentRepository findByEstimateId', ['estimate_id' => $estimateId]);

        $payments = Payment::where('estimate_id', $estimateId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        Log::info('PaymentRepository findByEstimateId result', [
            'estimate_id' => $estimateId,
            'count' => count($payments)
        ]);

        return $payments;
    }

    /**
     * 店舗IDで支払い情報を取得
     */
    public function findByStoreId(string $storeId): array
    {
        Log::info('PaymentRepository findByStoreId', ['store_id' => $storeId]);

        $payments = Payment::where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        Log::info('PaymentRepository findByStoreId result', [
            'store_id' => $storeId,
            'count' => count($payments)
        ]);

        return $payments;
    }

    /**
     * プロバイダーIDで支払い情報を取得
     */
    public function findByProviderId(string $providerId): ?Payment
    {
        Log::info('PaymentRepository findByProviderId', ['provider_id' => $providerId]);

        $payment = Payment::where('provider_id', $providerId)->first();

        Log::info('PaymentRepository findByProviderId result', [
            'provider_id' => $providerId,
            'found' => $payment !== null
        ]);

        return $payment;
    }

    /**
     * 支払いステータスで支払い情報を取得
     */
    public function findByStatus(int $status): array
    {
        Log::info('PaymentRepository findByStatus', ['status' => $status]);

        $payments = Payment::where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        Log::info('PaymentRepository findByStatus result', [
            'status' => $status,
            'count' => count($payments)
        ]);

        return $payments;
    }

    /**
     * 支払い情報を更新
     */
    public function update(string $id, array $data): bool
    {
        Log::info('PaymentRepository update', ['id' => $id, 'data' => $data]);

        $payment = Payment::find($id);
        if (!$payment) {
            Log::warning('PaymentRepository update: payment not found', ['id' => $id]);
            return false;
        }

        $result = $payment->update($data);

        Log::info('PaymentRepository update result', [
            'id' => $id,
            'success' => $result
        ]);

        return $result;
    }

    /**
     * 支払い情報を削除
     */
    public function delete(string $id): bool
    {
        Log::info('PaymentRepository delete', ['id' => $id]);

        $payment = Payment::find($id);
        if (!$payment) {
            Log::warning('PaymentRepository delete: payment not found', ['id' => $id]);
            return false;
        }

        $result = $payment->delete();

        Log::info('PaymentRepository delete result', [
            'id' => $id,
            'success' => $result
        ]);

        return $result;
    }

    /**
     * 見積もりと店舗の組み合わせで支払い情報を取得
     */
    public function findByEstimateAndStore(string $estimateId, string $storeId): array
    {
        Log::info('PaymentRepository findByEstimateAndStore', [
            'estimate_id' => $estimateId,
            'store_id' => $storeId
        ]);

        $payments = Payment::where('estimate_id', $estimateId)
            ->where('store_id', $storeId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        Log::info('PaymentRepository findByEstimateAndStore result', [
            'estimate_id' => $estimateId,
            'store_id' => $storeId,
            'count' => count($payments)
        ]);

        return $payments;
    }

    /**
     * 成功した支払いのみを取得
     */
    public function findSuccessfulPayments(): array
    {
        Log::info('PaymentRepository findSuccessfulPayments');

        $payments = Payment::where('status', Payment::STATUS_SUCCESS)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        Log::info('PaymentRepository findSuccessfulPayments result', [
            'count' => count($payments)
        ]);

        return $payments;
    }

    /**
     * 失敗した支払いのみを取得
     */
    public function findFailedPayments(): array
    {
        Log::info('PaymentRepository findFailedPayments');

        $payments = Payment::where('status', Payment::STATUS_FAILURE)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        Log::info('PaymentRepository findFailedPayments result', [
            'count' => count($payments)
        ]);

        return $payments;
    }
}
