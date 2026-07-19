<?php

namespace Whilesmart\Entitlements\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Models\CouponRedemption;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * Add to whatever owns entitlements (a workspace, organization, user). Exposes
 * the owner's subscriptions, overrides and redemptions, plus gating helpers
 * that defer to the bound Entitlements implementation.
 */
trait HasEntitlements
{
    /**
     * The owner's entitlement subscriptions. Named to avoid colliding with the
     * subscriptions() relation billing libraries (Cashier, Paddle) add to the
     * same model, so this trait composes with a payment adapter cleanly.
     */
    public function entitlementSubscriptions(): MorphMany
    {
        return $this->morphMany(config('entitlements.models.subscription', Subscription::class), 'owner');
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->entitlementSubscriptions()->active()->latest('id')->first();
    }

    public function entitlementOverrides(): MorphMany
    {
        return $this->morphMany(config('entitlements.models.entitlement', Entitlement::class), 'owner');
    }

    public function couponRedemptions(): MorphMany
    {
        return $this->morphMany(config('entitlements.models.coupon_redemption', CouponRedemption::class), 'owner');
    }

    public function entitledTo(string $feature): bool
    {
        return app(Entitlements::class)->allows($this, $feature);
    }

    public function limitFor(string $key): ?int
    {
        return app(Entitlements::class)->limit($this, $key);
    }
}
