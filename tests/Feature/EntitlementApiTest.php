<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Models\Coupon;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

class EntitlementApiTest extends TestCase
{
    private const OWNER = Workspace::class;

    private function snapshotUrl(int $ownerId): string
    {
        return '/api/entitlements?'.http_build_query([
            'owner_type' => self::OWNER,
            'owner_id' => $ownerId,
        ]);
    }

    #[Test]
    public function it_returns_the_owner_entitlement_snapshot(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create([
            'key' => 'pro',
            'name' => 'Pro',
            'features' => ['reports' => true, 'export' => false],
            'limits' => ['seats' => 5],
        ]);
        Subscription::factory()->create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'plan_id' => $plan->id,
        ]);
        Entitlement::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'feature' => 'export',
        ]);

        $this->getJson($this->snapshotUrl($owner->id))
            ->assertOk()
            ->assertJsonPath('data.plan.key', 'pro')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.features.reports', true)
            ->assertJsonPath('data.features.export', true)
            ->assertJsonPath('data.limits.seats', 5);
    }

    #[Test]
    public function it_requires_owner_fields_for_the_snapshot(): void
    {
        $this->getJson('/api/entitlements')
            ->assertStatus(422);
    }

    #[Test]
    public function it_redeems_a_coupon_over_http(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create();
        $coupon = Coupon::factory()->create([
            'code' => 'GRANT-PRO',
            'grants_plan_id' => $plan->id,
        ]);

        $this->postJson('/api/entitlements/redeem-coupon', [
            'owner_type' => self::OWNER,
            'owner_id' => $owner->id,
            'code' => 'GRANT-PRO',
        ])->assertCreated()
            ->assertJsonPath('data.grant', 'subscription');

        $this->assertDatabaseHas('subscriptions', [
            'owner_id' => $owner->id,
            'plan_id' => $plan->id,
            'status' => 'comped',
        ]);
    }

    #[Test]
    public function redeeming_an_unknown_coupon_is_a_404(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->postJson('/api/entitlements/redeem-coupon', [
            'owner_type' => self::OWNER,
            'owner_id' => $owner->id,
            'code' => 'NOPE',
        ])->assertNotFound();
    }

    #[Test]
    public function redeeming_an_expired_coupon_returns_422(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $coupon = Coupon::factory()->comp()->create([
            'code' => 'OLD',
            'expires_at' => now()->subDay(),
        ]);

        $this->postJson('/api/entitlements/redeem-coupon', [
            'owner_type' => self::OWNER,
            'owner_id' => $owner->id,
            'code' => 'OLD',
        ])->assertStatus(422);
    }
}
