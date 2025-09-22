<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRight extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'estimate_id',
        'store_id',
        'bid_id',
        'bid_amount_min',
        'bid_amount_max',
        'ranking',
        'is_notified',
        'granted_at',
    ];

    protected $casts = [
        'bid_amount_min' => 'integer',
        'bid_amount_max' => 'integer',
        'ranking' => 'integer',
        'is_notified' => 'boolean',
        'granted_at' => 'datetime',
    ];

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
     * 入札とのリレーション
     */
    public function bid(): BelongsTo
    {
        return $this->belongsTo(Bid::class);
    }

    /**
     * 通知済みかどうかを判定
     */
    public function isNotified(): bool
    {
        return $this->is_notified;
    }

    /**
     * 通知完了をマーク
     */
    public function markAsNotified(): bool
    {
        return $this->update(['is_notified' => true]);
    }
}
