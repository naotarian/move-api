<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Organization extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable, HasApiTokens;

    protected $table = 'organizations';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'status',
        'is_verified',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_verified' => 'boolean',
        'password' => 'hashed',
    ];

    // ステータス定数
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * この組織に属する店舗
     */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    /**
     * アクティブな店舗のみ
     */
    public function activeStores(): HasMany
    {
        return $this->hasMany(Store::class)->where('status', Store::STATUS_ACTIVE);
    }

    /**
     * 認証済み店舗のみ
     */
    public function verifiedStores(): HasMany
    {
        return $this->hasMany(Store::class)->where('is_verified', true);
    }

    /**
     * アクティブな組織かどうか
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 認証済み組織かどうか
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * アクティブな組織のみを取得
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 認証済み組織のみを取得
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * ステータスでフィルタ
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 店舗数を取得
     */
    public function getStoreCountAttribute(): int
    {
        return $this->stores()->count();
    }

    /**
     * アクティブな店舗数を取得
     */
    public function getActiveStoreCountAttribute(): int
    {
        return $this->activeStores()->count();
    }

    /**
     * 組織の支払い方法
     */
    public function paymentMethods()
    {
        return $this->hasMany(OrganizationPaymentMethod::class);
    }

    /**
     * 組織の既定の支払い方法
     */
    public function defaultPaymentMethod()
    {
        return $this->belongsTo(OrganizationPaymentMethod::class, 'org_default_payment_method_id');
    }
}
