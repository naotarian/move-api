<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LuggageMaster extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'luggage_master';

    protected $fillable = [
        'code',
        'name',
        'sub_label',
        'category_id',
        'description',
        'base_price',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * 荷物カテゴリーとのリレーション
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LuggageCategory::class, 'category_id');
    }

    /**
     * 見積もり荷物とのリレーション
     */
    public function estimateLuggage(): HasMany
    {
        return $this->hasMany(EstimateLuggage::class, 'luggage_id');
    }

    /**
     * 有効な荷物のみ取得
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順序でソート
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * カテゴリー別に取得
     */
    public function scopeByCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * コードで検索
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * 価格範囲で検索
     */
    public function scopePriceBetween($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('base_price', [$minPrice, $maxPrice]);
    }

    /**
     * カテゴリー名を取得
     */
    public function getCategoryNameAttribute()
    {
        return $this->category?->name;
    }

    /**
     * カテゴリーコードを取得
     */
    public function getCategoryCodeAttribute()
    {
        return $this->category?->code;
    }

    /**
     * 表示用の名前を取得（サブラベル含む）
     */
    public function getDisplayNameAttribute()
    {
        return $this->sub_label ? "{$this->name}（{$this->sub_label}）" : $this->name;
    }
}
