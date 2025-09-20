<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    use HasUlids;

    protected $table = 'bids';

    protected $fillable = [
        'estimate_bid_right_id',
        'bid_amount_min',
        'bid_amount_max',
        'bid_at',
    ];

    protected $casts = [
        'bid_at' => 'datetime',
    ];

    public function estimateBidRight(): BelongsTo
    {
        return $this->belongsTo(EstimateBidRight::class);
    }
}
