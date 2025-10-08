<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prefecture extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'region_id',
        'code',
        'name',
    ];

    protected $casts = [
        'code' => 'integer',
    ];

    /**
     * 都道府県が属する地域
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * 都道府県コードでソートするスコープ
     */
    public function scopeOrderByCode($query)
    {
        return $query->orderBy('code');
    }

    /**
     * 都道府県名で検索するスコープ
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }

    /**
     * 都道府県コードで検索するスコープ
     */
    public function scopeByCode($query, int $code)
    {
        return $query->where('code', $code);
    }

    /**
     * 地域IDで絞り込むスコープ
     */
    public function scopeByRegion($query, string $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * 地域名を含めて検索するスコープ
     */
    public function scopeWithRegion($query)
    {
        return $query->with('region');
    }
}
