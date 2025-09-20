<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmsVerificationCode extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'sms_verification_codes';

    public const STATUS_PENDING = 'pending';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    public const EXPIRY_MINUTES = 10; // 10分間有効

    protected $fillable = [
        'estimate_id',
        'phone',
        'code',
        'status',
        'expires_at',
        'used_at',
        'send_result',
        'retry_count',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'send_result' => 'array',
        'retry_count' => 'integer',
    ];

    /**
     * Estimateとのリレーション
     */
    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }

    /**
     * 有効なコードのスコープ
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('status', self::STATUS_PENDING);
    }

    /**
     * 特定の見積もりIDのコードのスコープ
     */
    public function scopeForEstimate($query, string $estimateId)
    {
        return $query->where('estimate_id', $estimateId);
    }

    /**
     * 6桁のランダムコードを生成
     */
    public static function generateCode(): string
    {
        return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * 見積もり用のSMS認証コードを作成（既存の未使用コードがあれば期限切れにする）
     */
    public static function createForEstimate(
        string $estimateId,
        string $phone
    ): self {
        return DB::transaction(function () use ($estimateId, $phone) {
            // 既存のpendingコードを一括で期限切れにする（より確実な方法）
            $expiredCount = self::where('estimate_id', $estimateId)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status' => self::STATUS_EXPIRED,
                    'updated_at' => now()
                ]);

            if ($expiredCount > 0) {
                Log::info('SmsVerificationCode: Expired existing codes', [
                    'estimate_id' => $estimateId,
                    'expired_count' => $expiredCount
                ]);
            }

            // 新しいコードを作成
            $newCode = self::create([
                'estimate_id' => $estimateId,
                'phone' => $phone,
                'code' => self::generateCode(),
                'status' => self::STATUS_PENDING,
                'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
                'retry_count' => 0,
            ]);

            Log::info('SmsVerificationCode: Created new code', [
                'estimate_id' => $estimateId,
                'phone' => $phone,
                'code_id' => $newCode->id,
                'code' => $newCode->code,
                'expires_at' => $newCode->expires_at->format('Y-m-d H:i:s'),
                'expired_old_codes' => $expiredCount
            ]);

            return $newCode;
        });
    }

    /**
     * コードが有効かどうかを判定
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at->isFuture();
    }

    /**
     * コードを使用済みにする
     */
    public function markAsUsed(): bool
    {
        return $this->update([
            'status' => self::STATUS_USED,
            'used_at' => now(),
        ]);
    }

    /**
     * コードを期限切れにする
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    /**
     * 再送回数を増加
     */
    public function incrementRetryCount(?array $sendResult = null): bool
    {
        return $this->update([
            'retry_count' => $this->retry_count + 1,
            'last_sent_at' => now(),
            'send_result' => $sendResult ?? $this->send_result,
        ]);
    }

    /**
     * コードでSMS認証コードを検索
     */
    public static function findValidCode(string $code): ?self
    {
        return self::where('code', $code)
            ->valid()
            ->first();
    }
}
