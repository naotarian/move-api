<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'estimate_id',
        'store_id',
        'amount_excluding_tax',
        'amount_including_tax',
        'tax_amount',
        'tax_rate',
        'payment_date',
        'payment_method',
        'status',
        'provider',
        'provider_id',
        'provider_url',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount_excluding_tax' => 'integer',
        'amount_including_tax' => 'integer',
        'tax_amount' => 'integer',
        'tax_rate' => 'integer',
        'payment_date' => 'datetime',
        'payment_method' => 'integer',
        'status' => 'integer',
    ];

    // 支払い方法の定数
    public const PAYMENT_METHOD_CREDIT_CARD = 1;
    public const PAYMENT_METHOD_BANK_TRANSFER = 2;

    // 支払いステータスの定数
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = 2;

    /**
     * 見積もりとのリレーション
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * 店舗とのリレーション
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * 支払い方法の表示名を取得
     */
    public function getPaymentMethodNameAttribute(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_METHOD_CREDIT_CARD => 'クレジットカード',
            self::PAYMENT_METHOD_BANK_TRANSFER => '銀行振込',
            default => '不明',
        };
    }

    /**
     * 支払いステータスの表示名を取得
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_SUCCESS => '成功',
            self::STATUS_FAILURE => '失敗',
            default => '不明',
        };
    }

    /**
     * 支払いが成功しているかチェック
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * 支払いが失敗しているかチェック
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILURE;
    }
}
