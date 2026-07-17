<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Enums\CouponType;
use Whilesmart\Entitlements\Enums\SubscriptionStatus;
use Whilesmart\Entitlements\Exceptions\CouponRedemptionException;
use Whilesmart\Entitlements\Models\Coupon;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;
use Whilesmart\Entitlements\Services\CouponService;

class CouponRedemptionTest extends TestCase
{
    private function service(): CouponService
    {
        return app(CouponService::class);
    }

    #[Test]
    public function a_plan_grant_coupon_creates_a_comped_subscription_that_entitles(): void
    {
        $this->enforceEntitlements();

        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create(['features' => ['reports' => true]]);
        $coupon = Coupon::factory()->create([
            'type' => CouponType::PlanGrant->value,
            'grants_plan_id' => $plan->id,
            'bypass' => false,
        ]);

        $result = $this->service()->redeem($coupon, $owner);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertSame(SubscriptionStatus::Comped, $result->status);
        $this->assertSame($plan->id, $result->plan_id);
        $this->assertTrue(app(Entitlements::class)->allows($owner, 'reports'));

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'owner_id' => $owner->id,
        ]);
    }

    #[Test]
    public function a_comp_coupon_with_bypass_creates_an_unlimited_comped_subscription(): void
    {
        $this->enforceEntitlements();

        $owner = Workspace::create(['name' => 'Acme']);
        $coupon = Coupon::factory()->comp()->create();

        $result = $this->service()->redeem($coupon, $owner);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertNull($result->plan_id);
        $this->assertTrue(app(Entitlements::class)->allows($owner, 'anything'));
    }

    #[Test]
    public function a_discount_coupon_records_an_entitlement_override_for_the_billing_adapter(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $coupon = Coupon::factory()->percentOff(25)->create();

        $result = $this->service()->redeem($coupon, $owner);

        $this->assertSame(25, $result->metadata['amount']);
        $this->assertSame('percent_off', $result->metadata['discount_type']);
        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'owner_id' => $owner->id,
        ]);
    }

    #[Test]
    public function an_expired_coupon_is_rejected_and_leaves_no_trace(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $coupon = Coupon::factory()->comp()->create(['expires_at' => now()->subDay()]);

        try {
            $this->service()->redeem($coupon, $owner);
            $this->fail('Expected a CouponRedemptionException.');
        } catch (CouponRedemptionException $e) {
            $this->assertStringContainsString('expired', $e->getMessage());
        }

        $this->assertDatabaseCount('coupon_redemptions', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    #[Test]
    public function a_coupon_over_its_redemption_limit_is_rejected(): void
    {
        $ownerA = Workspace::create(['name' => 'A']);
        $ownerB = Workspace::create(['name' => 'B']);
        $coupon = Coupon::factory()->comp()->create(['max_redemptions' => 1]);

        $this->service()->redeem($coupon, $ownerA);

        $this->expectException(CouponRedemptionException::class);
        $this->service()->redeem($coupon, $ownerB);
    }

    #[Test]
    public function a_plan_grant_without_a_plan_is_rejected(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $coupon = Coupon::factory()->create([
            'type' => CouponType::PlanGrant->value,
            'grants_plan_id' => null,
        ]);

        $this->expectException(CouponRedemptionException::class);
        $this->service()->redeem($coupon, $owner);
    }
}
