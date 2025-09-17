<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Estimate extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'estimates';

    // ステータス定数
    public const STATUS_DRAFT = 'draft';      // 公開前
    public const STATUS_PUBLISHED = 'published'; // 公開中
    public const STATUS_CLOSED = 'closed';    // 公開終了

    // 引越し日タイプ定数
    public const MOVING_DATE_TYPE_UNDECIDED = 'undecided'; // 決まっていない
    public const MOVING_DATE_TYPE_DECIDED = 'decided';     // 決まっている

    // 引越し期間定数
    public const MOVING_PERIOD_EARLY = 'early';   // 上旬
    public const MOVING_PERIOD_MIDDLE = 'middle'; // 中旬
    public const MOVING_PERIOD_LATE = 'late';     // 下旬

    // 作業開始時間タイプ定数
    public const WORK_START_TIME_TYPE_ANYTIME = 'anytime';   // いつでも
    public const WORK_START_TIME_TYPE_SPECIFIC = 'specific'; // 指定する

    // 作業開始時間定数
    public const WORK_START_TIME_MORNING = 'morning';   // 午前中
    public const WORK_START_TIME_AFTERNOON = 'afternoon'; // 12時~15時
    public const WORK_START_TIME_EVENING = 'evening';   // 15時以降

    protected $fillable = [
        'name',
        'name_furigana',
        'phone',
        'email',
        'people_count',
        'moving_date_type',
        'moving_year_month',
        'moving_period',
        'moving_specific_date',
        'work_start_time_type',
        'work_start_time',
        'other_luggage',
        'status',
    ];

    protected $casts = [
        'moving_specific_date' => 'date',
        'people_count' => 'integer',
    ];

    /**
     * 引越し元住所とのリレーション
     */
    public function movingFromAddress(): HasOne
    {
        return $this->hasOne(MovingFromAddress::class);
    }

    /**
     * 引越し先住所とのリレーション
     */
    public function movingToAddress(): HasOne
    {
        return $this->hasOne(MovingToAddress::class);
    }

    /**
     * 荷物情報とのリレーション
     */
    public function luggageItems(): HasMany
    {
        return $this->hasMany(EstimateLuggage::class);
    }

    /**
     * 見積もり結果とのリレーション
     */
    public function results(): HasMany
    {
        return $this->hasMany(EstimateResult::class);
    }

    /**
     * ステータススコープ
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 公開前の見積もりを取得
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * 公開中の見積もりを取得
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * 公開終了の見積もりを取得
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * 期間スコープ
     */
    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }


    /**
     * 引越し日スコープ
     */
    public function scopeMovingDateBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('moving_specific_date', [$startDate, $endDate]);
    }

    /**
     * メールアドレススコープ
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * 最新の見積もり結果を取得
     */
    public function getLatestResultAttribute()
    {
        return $this->results()->latest('quoted_at')->first();
    }

    /**
     * 承認された見積もり結果を取得
     */
    public function getAcceptedResultAttribute()
    {
        return $this->results()->where('status', 'accepted')->first();
    }

    /**
     * 見積もり結果の件数を取得
     */
    public function getResultsCountAttribute()
    {
        return $this->results()->count();
    }
}
