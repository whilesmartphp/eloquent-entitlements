<?php

namespace Whilesmart\Entitlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whilesmart\Entitlements\Database\Factories\CouponFactory;
use Whilesmart\Entitlements\Enums\CouponType;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => CouponType::class,
        'bypass' => 'boolean',
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'max_redemptions' => 'integer',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('entitlements.coupons_table', 'coupons');
    }

    public function grantsPlan(): BelongsTo
    {
        return $this->belongsTo(config('entitlements.models.plan', Plan::class), 'grants_plan_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(config('entitlements.models.coupon_redemption', CouponRedemption::class), 'coupon_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_redemptions !== null
            && $this->redemptions()->count() >= $this->max_redemptions;
    }

    protected static function newFactory(): CouponFactory
    {
        return CouponFactory::new();
    }
}
