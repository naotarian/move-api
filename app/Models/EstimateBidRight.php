<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateBidRight extends Model
{
    use HasUlids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'estimate_bid_rights';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'estimate_id',
        'store_id',
        'status',
        'payment_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
    ];

    // ステータスの定数
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 2;

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
     * 支払いとのリレーション
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * ステータスの表示名を取得
     */
    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => '有効',
            self::STATUS_INACTIVE => '無効',
            default => '不明',
        };
    }

    /**
     * 入札権が有効かチェック
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 入札権が無効かチェック
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * 入札権を有効にする
     */
    public function activate(): bool
    {
        $this->status = self::STATUS_ACTIVE;
        return $this->save();
    }

    /**
     * 入札権を無効にする
     */
    public function deactivate(): bool
    {
        $this->status = self::STATUS_INACTIVE;
        return $this->save();
    }

    /**
     * 特定の見積もりと店舗の組み合わせで入札権を検索
     */
    public static function findByEstimateAndStore(string $estimateId, string $storeId): ?self
    {
        return self::where('estimate_id', $estimateId)
            ->where('store_id', $storeId)
            ->first();
    }

    /**
     * 有効な入札権のみを取得するスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 無効な入札権のみを取得するスコープ
     */
    public function scopeInactive($query)
    {
        return $query->where('status', self::STATUS_INACTIVE);
    }
}
