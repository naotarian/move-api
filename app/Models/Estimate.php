<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use DateTimeInterface;

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
        'email_verified',
        'email_verified_at',
        'phone_verified',
        'phone_verified_at',
        'bid_deadline',
        'straight_distance_km',
    ];

    protected $casts = [
        'moving_specific_date' => 'date',
        'people_count' => 'integer',
        'email_verified' => 'boolean',
        'email_verified_at' => 'datetime',
        'phone_verified' => 'boolean',
        'phone_verified_at' => 'datetime',
        'bid_deadline' => 'datetime',
        'straight_distance_km' => 'integer',
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
     * メール認証トークンとのリレーション
     */
    public function emailVerificationTokens(): HasMany
    {
        return $this->hasMany(EmailVerificationToken::class);
    }

    /**
     * SMS認証コードとのリレーション
     */
    public function smsVerificationCodes(): HasMany
    {
        return $this->hasMany(SmsVerificationCode::class);
    }

    /**
     * 入札権とのリレーション
     */
    public function estimateBidRights(): HasMany
    {
        return $this->hasMany(EstimateBidRight::class);
    }

    /**
     * 入札とのリレーション（EstimateBidRight経由）
     */
    public function bids(): HasManyThrough
    {
        return $this->hasManyThrough(
            Bid::class,
            EstimateBidRight::class,
            'estimate_id', // EstimateBidRightの外部キー
            'estimate_bid_right_id', // Bidの外部キー
            'id', // Estimateのローカルキー
            'id'  // EstimateBidRightのローカルキー
        );
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
     * メール認証済みスコープ
     */
    public function scopeEmailVerified($query)
    {
        return $query->where('email_verified', true);
    }

    /**
     * 電話番号認証済みスコープ
     */
    public function scopePhoneVerified($query)
    {
        return $query->where('phone_verified', true);
    }

    /**
     * 完全認証済みスコープ（メール・電話番号両方認証済み）
     */
    public function scopeFullyVerified($query)
    {
        return $query->where('email_verified', true)
            ->where('phone_verified', true);
    }

    /**
     * 入札期限内のスコープ
     */
    public function scopeWithinBidDeadline($query)
    {
        return $query->where('bid_deadline', '>', now());
    }

    /**
     * 入札期限切れのスコープ
     */
    public function scopeBidDeadlineExpired($query)
    {
        return $query->where('bid_deadline', '<=', now());
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

    /**
     * メールアドレス認証を完了させる
     */
    public function markEmailAsVerified(): bool
    {
        return $this->update([
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * 電話番号認証を完了させる
     */
    public function markPhoneAsVerified(): bool
    {
        $bidDeadline = $this->calculateBidDeadline();

        $updated = $this->update([
            'phone_verified' => true,
            'phone_verified_at' => now(),
            'bid_deadline' => $bidDeadline,
            'status' => self::STATUS_PUBLISHED, // 電話認証完了で公開状態に
        ]);

        \Log::info('Estimate: Phone verification completed and bid deadline set', [
            'estimate_id' => $this->id,
            'bid_deadline' => $bidDeadline->toDateTimeString(),
            'status' => self::STATUS_PUBLISHED
        ]);

        return $updated;
    }

    /**
     * 入札期限を計算する（24時間後の直近1時間単位）
     */
    public function calculateBidDeadline(): \Carbon\Carbon
    {
        // 24時間後の時刻を取得
        \Log::info(now()->toDateTimeString());
        $deadline = now()->addHours(24);

        // 分と秒を0にして1時間単位に切り上げ
        if ($deadline->minute > 0 || $deadline->second > 0) {
            $deadline = $deadline->addHour()->startOfHour();
        } else {
            $deadline = $deadline->startOfHour();
        }

        return $deadline;
    }

    /**
     * 完全認証済みかどうかを判定
     */
    public function isFullyVerified(): bool
    {
        return $this->email_verified && $this->phone_verified;
    }

    /**
     * 認証進捗率を取得（0-100%）
     */
    public function getVerificationProgressAttribute(): int
    {
        $progress = 0;
        if ($this->email_verified) {
            $progress += 50;
        }
        if ($this->phone_verified) {
            $progress += 50;
        }
        return $progress;
    }

    /**
     * 入札期限が設定されているかどうかを判定
     */
    public function hasBidDeadline(): bool
    {
        return $this->bid_deadline !== null;
    }

    /**
     * 入札期限内かどうかを判定
     */
    public function isWithinBidDeadline(): bool
    {
        return $this->hasBidDeadline() && $this->bid_deadline > now();
    }

    /**
     * 入札期限切れかどうかを判定
     */
    public function isBidDeadlineExpired(): bool
    {
        return $this->hasBidDeadline() && $this->bid_deadline <= now();
    }

    /**
     * 入札期限までの残り時間（時間単位）を取得
     */
    public function getRemainingBidHoursAttribute(): ?int
    {
        if (!$this->hasBidDeadline()) {
            return null;
        }

        $remaining = now()->diffInHours($this->bid_deadline, false);
        return $remaining > 0 ? $remaining : 0;
    }

    /**
     * 入札権購入期限を取得（入札期限の15分前）
     */
    public function getBidRightPurchaseDeadline(): ?\Carbon\Carbon
    {
        if (!$this->hasBidDeadline()) {
            return null;
        }

        return $this->bid_deadline->copy()->subMinutes(15);
    }

    /**
     * 入札権購入期限内かどうかを判定
     */
    public function isWithinPurchaseDeadline(): bool
    {
        $purchaseDeadline = $this->getBidRightPurchaseDeadline();

        if (!$purchaseDeadline) {
            return false;
        }

        return now() <= $purchaseDeadline;
    }

    /**
     * 入札権購入期限切れかどうかを判定
     */
    public function isPurchaseDeadlineExpired(): bool
    {
        $purchaseDeadline = $this->getBidRightPurchaseDeadline();

        if (!$purchaseDeadline) {
            return true; // 入札期限が設定されていない場合は購入不可
        }

        return now() > $purchaseDeadline;
    }

    /**
     * 入札権購入期限までの残り時間（分単位）を取得
     */
    public function getRemainingPurchaseMinutesAttribute(): ?int
    {
        $purchaseDeadline = $this->getBidRightPurchaseDeadline();

        if (!$purchaseDeadline) {
            return null;
        }

        $remaining = now()->diffInMinutes($purchaseDeadline, false);
        return $remaining > 0 ? $remaining : 0;
    }
}
