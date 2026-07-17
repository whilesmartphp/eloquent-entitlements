<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\ArrayPlanSource;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Contracts\PlanSource;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Subscription;
use Whilesmart\Entitlements\Support\EloquentPlanSource;
use Whilesmart\Entitlements\Support\ResolvedPlan;

class PlanSourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enforceEntitlements();
    }

    #[Test]
    public function the_default_plan_source_reads_this_packages_tables(): void
    {
        $this->assertInstanceOf(EloquentPlanSource::class, app(PlanSource::class));
    }

    #[Test]
    public function a_host_plan_source_entitles_without_any_subscription_rows(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->swap(PlanSource::class, new ArrayPlanSource([
            $owner->getKey() => ResolvedPlan::of(
                features: ['reports' => true, 'export' => false],
                limits: ['seats' => 5],
                meters: ['api_calls' => 100],
            ),
        ]));

        $entitlements = app(Entitlements::class);

        $this->assertTrue($entitlements->allows($owner, 'reports'));
        $this->assertFalse($entitlements->allows($owner, 'export'));
        $this->assertSame(5, $entitlements->limit($owner, 'seats'));
        $this->assertSame(0, Subscription::count());
    }

    #[Test]
    public function an_owner_the_source_does_not_know_is_entitled_to_nothing(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->swap(PlanSource::class, new ArrayPlanSource);

        $entitlements = app(Entitlements::class);

        $this->assertFalse($entitlements->allows($owner, 'reports'));
        $this->assertNull($entitlements->limit($owner, 'seats'));
    }

    #[Test]
    public function a_source_may_report_an_unlimited_plan(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->swap(PlanSource::class, new ArrayPlanSource([
            $owner->getKey() => ResolvedPlan::unlimited(),
        ]));

        $entitlements = app(Entitlements::class);

        $this->assertTrue($entitlements->allows($owner, 'anything'));
        $this->assertNull($entitlements->limit($owner, 'seats'));
        $this->assertSame(INF, $entitlements->remaining($owner, 'api_calls'));
    }

    #[Test]
    public function an_override_still_wins_over_a_host_plan_source(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->swap(PlanSource::class, new ArrayPlanSource([
            $owner->getKey() => ResolvedPlan::of(
                features: ['export' => false],
                limits: ['seats' => 5],
            ),
        ]));

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
    public function a_source_may_nest_feature_keys(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);

        $this->swap(PlanSource::class, new ArrayPlanSource([
            $owner->getKey() => ResolvedPlan::of(
                features: ['reports' => ['export' => true]],
            ),
        ]));

        $this->assertTrue(app(Entitlements::class)->allows($owner, 'reports.export'));
    }
}
