<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateLuggage extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'estimate_luggage';

    protected $fillable = [
        'estimate_id',
        'luggage_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    /**
     * 見積もりとのリレーション
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * 荷物マスタとのリレーション
     */
    public function luggage(): BelongsTo
    {
        return $this->belongsTo(LuggageMaster::class, 'luggage_id');
    }

    /**
     * 荷物マスタとのリレーション（エイリアス）
     */
    public function luggageMaster(): BelongsTo
    {
        return $this->belongsTo(LuggageMaster::class, 'luggage_id');
    }

    /**
     * 数量が0より大きいスコープ
     */
    public function scopeWithQuantity($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * 荷物IDスコープ
     */
    public function scopeByLuggageId($query, string $luggageId)
    {
        return $query->where('luggage_id', $luggageId);
    }

    /**
     * カテゴリー別にグループ化
     */
    public function scopeGroupByCategory($query)
    {
        return $query->join('luggage_master', 'estimate_luggage.luggage_id', '=', 'luggage_master.id')
            ->join('luggage_categories', 'luggage_master.category_id', '=', 'luggage_categories.id')
            ->selectRaw('luggage_categories.name as category_name, COUNT(*) as count, SUM(estimate_luggage.quantity) as total_quantity')
            ->groupBy('luggage_categories.id', 'luggage_categories.name');
    }

    /**
     * 荷物名を取得
     */
    public function getLuggageNameAttribute()
    {
        return $this->luggage?->name;
    }

    /**
     * 荷物のサブラベルを取得
     */
    public function getLuggageSubLabelAttribute()
    {
        return $this->luggage?->sub_label;
    }

    /**
     * カテゴリー名を取得
     */
    public function getCategoryNameAttribute()
    {
        return $this->luggage?->category?->name;
    }

    /**
     * 表示用の名前を取得
     */
    public function getDisplayNameAttribute()
    {
        return $this->luggage?->display_name;
    }
}
