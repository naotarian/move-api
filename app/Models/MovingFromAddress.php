<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovingFromAddress extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'moving_from_addresses';

    // 建物タイプ定数
    public const BUILDING_TYPE_MANSION = 'mansion';
    public const BUILDING_TYPE_APARTMENT = 'apartment';
    public const BUILDING_TYPE_HOUSE = 'house';
    public const BUILDING_TYPE_OTHER = 'other';

    // エレベーター定数
    public const ELEVATOR_YES = 'yes';
    public const ELEVATOR_NO = 'no';

    protected $fillable = [
        'estimate_id',
        'zipcode',
        'prefecture',
        'prefecture_code',
        'region_code',
        'street_address',
        'building_details',
        'building_type',
        'room_layout',
        'floor',
        'elevator',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'estimate_id' => 'string',
        'prefecture_code' => 'integer',
        'region_code' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * 見積もりとのリレーション
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * 完全な住所を取得
     */
    public function getFullAddressAttribute(): string
    {
        $address = $this->prefecture . $this->street_address;
        if ($this->building_details) {
            $address .= ' ' . $this->building_details;
        }
        return $address;
    }

    /**
     * 郵便番号付きの住所を取得
     */
    public function getAddressWithZipcodeAttribute(): string
    {
        return "〒{$this->zipcode} {$this->full_address}";
    }
}
