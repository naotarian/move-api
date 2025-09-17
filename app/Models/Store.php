<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Store extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable, HasApiTokens;

    protected $table = 'stores';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'license_number',
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
     * アクティブな店舗かどうか
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * 認証済み店舗かどうか
     */
    public function isVerified(): bool
    {
        return $this->is_verified;
    }

    /**
     * アクティブな店舗のみを取得
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * 認証済み店舗のみを取得
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
}
