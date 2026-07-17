<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Contracts\BillingProvider;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Contracts\UsageMeter;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Support\AllowAllEntitlements;
use Whilesmart\Entitlements\Support\NullBillingProvider;

class DefaultBindingsTest extends TestCase
{
    #[Test]
    public function the_default_entitlements_binding_allows_everything(): void
    {
        $entitlements = app(Entitlements::class);
        $owner = Workspace::create(['name' => 'Acme']);

        $this->assertInstanceOf(AllowAllEntitlements::class, $entitlements);
        $this->assertTrue($entitlements->allows($owner, 'anything'));
        $this->assertNull($entitlements->limit($owner, 'seats'));
        $this->assertSame(INF, $entitlements->remaining($owner, 'api_calls'));

        // No-op consume must not throw.
        $entitlements->consume($owner, 'api_calls', 5);
    }

    #[Test]
    public function the_null_usage_meter_reports_unlimited(): void
    {
        $meter = app(UsageMeter::class);
        $owner = Workspace::create(['name' => 'Acme']);

        $this->assertSame(INF, $meter->remaining($owner, 'api_calls', 10));
        $meter->consume($owner, 'api_calls', 5);
    }

    #[Test]
    public function the_null_billing_provider_refuses_checkout_and_no_ops_the_rest(): void
    {
        $provider = app(BillingProvider::class);
        $owner = Workspace::create(['name' => 'Acme']);
        $plan = Plan::factory()->create();

        $this->assertInstanceOf(NullBillingProvider::class, $provider);

        $provider->syncFromWebhook(['any' => 'payload']);

        $this->expectException(RuntimeException::class);
        $provider->createCheckout($owner, $plan);
    }
}
