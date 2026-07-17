<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Contracts\OwnerResolver;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

class RequireFeatureMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enforceEntitlements();

        Route::middleware('feature:reports')->get('/_test/gated', fn () => response()->json(['ok' => true]));
    }

    private function ownerIs(?Workspace $owner): void
    {
        $this->swap(OwnerResolver::class, new class($owner) implements OwnerResolver
        {
            public function __construct(private ?Workspace $owner) {}

            public function resolve(Request $request): ?Model
            {
                return $this->owner;
            }
        });
    }

    private function subscribe(Workspace $owner, array $features): void
    {
        Subscription::factory()->create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'plan_id' => Plan::factory()->create(['features' => $features])->getKey(),
        ]);
    }

    #[Test]
    public function it_passes_when_the_owner_is_entitled(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $this->subscribe($owner, ['reports' => true]);
        $this->ownerIs($owner);

        $this->getJson('/_test/gated')->assertOk()->assertJsonPath('ok', true);
    }

    #[Test]
    public function it_answers_402_with_the_reason_when_the_feature_is_off_the_plan(): void
    {
        $owner = Workspace::create(['name' => 'Acme']);
        $this->subscribe($owner, ['reports' => false]);
        $this->ownerIs($owner);

        $this->getJson('/_test/gated')
            ->assertStatus(402)
            ->assertJsonPath('feature', 'reports')
            ->assertJsonPath('reason', 'feature_not_in_plan')
            ->assertJsonPath('error', 'PaymentRequired');
    }

    #[Test]
    public function it_answers_402_when_the_resolver_finds_no_owner(): void
    {
        $this->ownerIs(null);

        $this->getJson('/_test/gated')
            ->assertStatus(402)
            ->assertJsonPath('reason', 'no_owner');
    }
}
