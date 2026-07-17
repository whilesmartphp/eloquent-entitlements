<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

class AccessResultTest extends TestCase
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
    public function an_allowed_check_carries_the_feature_and_no_reason(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $this->subscribe($owner, Plan::factory()->create(['features' => ['reports' => true]]));

        $result = app(Entitlements::class)->check($owner, 'reports');

        $this->assertTrue($result->allowed);
        $this->assertSame('reports', $result->feature);
        $this->assertNull($result->reason);
    }

    #[Test]
    public function a_feature_off_the_plan_is_denied_with_a_reason(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $this->subscribe($owner, Plan::factory()->create(['features' => ['reports' => false]]));

        $result = app(Entitlements::class)->check($owner, 'reports');

        $this->assertFalse($result->allowed);
        $this->assertSame('feature_not_in_plan', $result->reason);
    }

    #[Test]
    public function an_owner_without_a_plan_is_denied_as_such(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->assertSame('no_plan', app(Entitlements::class)->check($owner, 'reports')->reason);
    }

    #[Test]
    public function a_null_owner_is_denied_as_such(): void
    {
        $this->assertSame('no_owner', app(Entitlements::class)->check(null, 'reports')->reason);
    }

    #[Test]
    public function allows_agrees_with_check(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $this->subscribe($owner, Plan::factory()->create(['features' => ['reports' => true, 'export' => false]]));

        $entitlements = app(Entitlements::class);

        $this->assertSame($entitlements->check($owner, 'reports')->allowed, $entitlements->allows($owner, 'reports'));
        $this->assertSame($entitlements->check($owner, 'export')->allowed, $entitlements->allows($owner, 'export'));
    }
}
