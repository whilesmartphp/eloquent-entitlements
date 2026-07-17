<?php

namespace Whilesmart\Entitlements\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponRedemption extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'redeemed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('entitlements.coupon_redemptions_table', 'entitlement_coupon_redemptions');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(config('entitlements.models.coupon', Coupon::class), 'coupon_id');
    }
}
