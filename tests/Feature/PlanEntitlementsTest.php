<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

class PlanEntitlementsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enforceEntitlements();
    }

    private function subscribe(Workspace $owner, ?Plan $plan, array $attributes = []): Subscription
    {
        return Subscription::factory()->create(array_merge([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'plan_id' => $plan?->getKey(),
        ], $attributes));
    }

    #[Test]
    public function it_reads_features_and_limits_from_the_active_subscription_plan(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create([
            'features' => ['reports' => true, 'export' => false],
            'limits' => ['seats' => 5],
        ]);
        $this->subscribe($owner, $plan);

        $entitlements = app(Entitlements::class);

        $this->assertTrue($entitlements->allows($owner, 'reports'));
        $this->assertFalse($entitlements->allows($owner, 'export'));
        $this->assertFalse($entitlements->allows($owner, 'unknown'));
        $this->assertSame(5, $entitlements->limit($owner, 'seats'));
    }

    #[Test]
    public function an_owner_without_a_subscription_is_entitled_to_nothing(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $entitlements = app(Entitlements::class);

        $this->assertFalse($entitlements->allows($owner, 'reports'));
        $this->assertNull($entitlements->limit($owner, 'seats'));
    }

    #[Test]
    public function a_canceled_subscription_does_not_entitle(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create(['features' => ['reports' => true]]);
        $this->subscribe($owner, $plan, ['status' => 'canceled']);

        $this->assertFalse(app(Entitlements::class)->allows($owner, 'reports'));
    }

    #[Test]
    public function an_override_wins_over_the_plan(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create([
            'features' => ['export' => false],
            'limits' => ['seats' => 5],
        ]);
        $this->subscribe($owner, $plan);

        Entitlement::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'feature' => 'export',
        ]);
        Entitlement::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'limit_key' => 'seats',
            'limit_value' => 50,
        ]);

        $entitlements = app(Entitlements::class);

        $this->assertTrue($entitlements->allows($owner, 'export'));
        $this->assertSame(50, $entitlements->limit($owner, 'seats'));
    }

    #[Test]
    public function a_comped_subscription_with_no_plan_is_unlimited(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $this->subscribe($owner, null, ['status' => 'comped', 'current_period_end' => null]);

        $entitlements = app(Entitlements::class);

        $this->assertTrue($entitlements->allows($owner, 'anything'));
        $this->assertNull($entitlements->limit($owner, 'seats'));
    }
}
