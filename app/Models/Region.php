<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'code',
        'name',
    ];

    protected $casts = [
        'code' => 'integer',
    ];

    /**
     * 地域に属する都道府県
     */
    public function prefectures(): HasMany
    {
        return $this->hasMany(Prefecture::class);
    }

    /**
     * 地域コードでソートするスコープ
     */
    public function scopeOrderByCode($query)
    {
        return $query->orderBy('code');
    }

    /**
     * 地域名で検索するスコープ
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', '%' . $name . '%');
    }

    /**
     * 地域コードで検索するスコープ
     */
    public function scopeByCode($query, int $code)
    {
        return $query->where('code', $code);
    }
}
