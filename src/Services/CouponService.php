<?php

namespace Whilesmart\Entitlements\Services;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Enums\CouponType;
use Whilesmart\Entitlements\Enums\SubscriptionStatus;
use Whilesmart\Entitlements\Exceptions\CouponRedemptionException;
use Whilesmart\Entitlements\Models\Coupon;
use Whilesmart\Entitlements\Models\CouponRedemption;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * Redeems a coupon for an owner. Every redemption is recorded for audit. No
 * provider or charge is ever involved; discounts are captured for the host's
 * billing adapter to apply at checkout.
 */
class CouponService
{
    /**
     * @throws CouponRedemptionException
     */
    public function redeem(Coupon $coupon, Model $owner): Subscription|Entitlement
    {
        $this->assertRedeemable($coupon);

        if ($coupon->type === CouponType::PlanGrant && $coupon->grants_plan_id === null) {
            throw new CouponRedemptionException('A plan_grant coupon must reference a plan to grant.');
        }

        $this->record($coupon, $owner);

        return match ($coupon->type) {
            CouponType::Comp, CouponType::PlanGrant => $this->grantSubscription($coupon, $owner),
            CouponType::PercentOff, CouponType::FixedOff => $this->grantDiscount($coupon, $owner),
        };
    }

    private function assertRedeemable(Coupon $coupon): void
    {
        if ($coupon->isExpired()) {
            throw new CouponRedemptionException('This coupon has expired.');
        }

        if ($coupon->isExhausted()) {
            throw new CouponRedemptionException('This coupon has reached its redemption limit.');
        }
    }

    private function record(Coupon $coupon, Model $owner): void
    {
        $model = config('entitlements.models.coupon_redemption', CouponRedemption::class);

        $model::create([
            'coupon_id' => $coupon->getKey(),
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'redeemed_at' => now(),
        ]);
    }

    /**
     * A comped subscription against the granted plan. When the coupon carries a
     * full bypass (or no plan), the subscription has no plan and reads as
     * unlimited.
     */
    private function grantSubscription(Coupon $coupon, Model $owner): Subscription
    {
        $model = config('entitlements.models.subscription', Subscription::class);

        return $model::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'plan_id' => $coupon->bypass ? null : $coupon->grants_plan_id,
            'status' => SubscriptionStatus::Comped->value,
            'metadata' => ['coupon_code' => $coupon->code],
        ]);
    }

    /**
     * A price discount does not gate anything by itself; it is recorded as a
     * per-owner override so the host's billing adapter can apply it at checkout.
     */
    private function grantDiscount(Coupon $coupon, Model $owner): Entitlement
    {
        $model = config('entitlements.models.entitlement', Entitlement::class);

        return $model::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'metadata' => [
                'coupon_code' => $coupon->code,
                'discount_type' => $coupon->type->value,
                'amount' => $coupon->amount,
            ],
        ]);
    }
}
