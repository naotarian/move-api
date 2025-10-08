<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class OrganizationPaymentMethod extends Model
{
    use HasUlids;

    protected $table = 'organization_payment_methods';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    // statusの定数
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'organization_id',
        'stripe_payment_method_id',
        'type',
        'brand',
        'last4',
        'exp_year',
        'exp_month',
        'fingerprint',
        'status',
        'billing_name',
        'billing_zipcode',
        'billing_address',
        'billing_email',
        'label',
    ];

    /**
     * 組織とのリレーション
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
