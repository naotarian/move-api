<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class EmailVerificationToken extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'email_verification_tokens';

    public const STATUS_PENDING = 'pending';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';

    public const EXPIRY_HOURS = 24; // 24時間有効

    protected $fillable = [
        'estimate_id',
        'email',
        'token',
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
     * 有効なトークンのスコープ
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('status', self::STATUS_PENDING);
    }

    /**
     * 特定の見積もりIDのトークンのスコープ
     */
    public function scopeForEstimate($query, string $estimateId)
    {
        return $query->where('estimate_id', $estimateId);
    }

    /**
     * 64文字のランダムトークンを生成
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * 見積もり用のメール認証トークンを作成（既存の未使用トークンがあれば期限切れにする）
     */
    public static function createForEstimate(
        string $estimateId,
        string $email
    ): self {
        return DB::transaction(function () use ($estimateId, $email) {
            // 既存のpendingトークンを一括で期限切れにする（より確実な方法）
            $expiredCount = self::where('estimate_id', $estimateId)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status' => self::STATUS_EXPIRED,
                    'updated_at' => now()
                ]);

            if ($expiredCount > 0) {
                Log::info('EmailVerificationToken: Expired existing tokens', [
                    'estimate_id' => $estimateId,
                    'expired_count' => $expiredCount
                ]);
            }

            // 新しいトークンを作成
            $newToken = self::create([
                'estimate_id' => $estimateId,
                'email' => $email,
                'token' => self::generateToken(),
                'status' => self::STATUS_PENDING,
                'expires_at' => now()->addHours(self::EXPIRY_HOURS),
                'retry_count' => 0,
            ]);

            Log::info('EmailVerificationToken: Created new token', [
                'estimate_id' => $estimateId,
                'email' => $email,
                'token_id' => $newToken->id,
                'token' => substr($newToken->token, 0, 10) . '...',
                'expires_at' => $newToken->expires_at->format('Y-m-d H:i:s'),
                'expired_old_tokens' => $expiredCount
            ]);

            return $newToken;
        });
    }

    /**
     * トークンが有効かどうかを判定
     */
    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at->isFuture();
    }

    /**
     * トークンを使用済みにする
     */
    public function markAsUsed(): bool
    {
        return $this->update([
            'status' => self::STATUS_USED,
            'used_at' => now(),
        ]);
    }

    /**
     * トークンを期限切れにする
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
     * トークンでメール認証トークンを検索
     */
    public static function findValidToken(string $token): ?self
    {
        return self::where('token', $token)
            ->valid()
            ->first();
    }
}
