<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LuggageCategory extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'luggage_categories';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * 荷物マスタとのリレーション
     */
    public function luggageItems(): HasMany
    {
        return $this->hasMany(LuggageMaster::class, 'category_id');
    }

    /**
     * 有効なカテゴリーのみ取得
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
     * コードで検索
     */
    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    /**
     * 有効な荷物アイテムを取得
     */
    public function getActiveLuggageItemsAttribute()
    {
        return $this->luggageItems()->active()->ordered()->get();
    }
}
