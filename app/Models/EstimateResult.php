<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateResult extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'estimate_results';

    protected $fillable = [
        'estimate_id',
        'company_name',
        'company_contact',
        'company_phone',
        'company_email',
        'estimated_price',
        'notes',
        'status',
        'quoted_at',
        'expires_at',
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
        'quoted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * 見積もりとのリレーション
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * ステータススコープ
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 承認済みスコープ
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * 有効期限内スコープ
     */
    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * 業者名スコープ
     */
    public function scopeByCompany($query, string $companyName)
    {
        return $query->where('company_name', $companyName);
    }

    /**
     * 価格範囲スコープ
     */
    public function scopePriceBetween($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('estimated_price', [$minPrice, $maxPrice]);
    }

    /**
     * 見積もり日時スコープ
     */
    public function scopeQuotedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('quoted_at', [$startDate, $endDate]);
    }

    /**
     * 有効期限切れスコープ
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * 最安値かどうかを判定
     */
    public function isLowestPrice($estimateId): bool
    {
        $lowestPrice = static::where('estimate_id', $estimateId)
                           ->where('status', '!=', 'rejected')
                           ->min('estimated_price');
        
        return $this->estimated_price == $lowestPrice;
    }

    /**
     * 有効期限が切れているかどうかを判定
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * 承認可能かどうかを判定
     */
    public function canBeAccepted(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }
}
