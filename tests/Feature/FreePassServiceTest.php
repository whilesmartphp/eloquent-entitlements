<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Exceptions\FreePassException;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;
use Whilesmart\Entitlements\Services\FreePassService;

class FreePassServiceTest extends TestCase
{
    private Workspace $owner;

    private Plan $starter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->owner = Workspace::create(['name' => 'Acme']);
        $this->starter = Plan::factory()->create(['key' => 'starter', 'name' => 'Starter', 'features' => ['reviews' => true], 'limits' => ['seats' => 5]]);
    }

    private function passes(): FreePassService
    {
        return app(FreePassService::class);
    }

    private function snapshot()
    {
        return $this->getJson('/api/entitlements?'.http_build_query([
            'owner_type' => $this->owner->getMorphClass(),
            'owner_id' => $this->owner->getKey(),
        ]));
    }

    #[Test]
    public function a_pass_gives_the_plan_until_its_end_date(): void
    {
        $pass = $this->passes()->grant($this->owner, $this->starter, now()->addDays(30)->endOfDay());

        $this->snapshot()
            ->assertJsonPath('data.plan.key', 'starter')
            ->assertJsonPath('data.status', 'comped')
            ->assertJsonPath('data.features.reviews', true)
            ->assertJsonPath('data.limits.seats', 5);
        $this->assertTrue($this->passes()->running($pass));

        $this->travel(31)->days();

        $this->snapshot()->assertJsonPath('data.plan', null);
        $this->assertFalse($this->passes()->running($pass->fresh()));
    }

    #[Test]
    public function stopping_a_pass_takes_the_plan_away_at_once(): void
    {
        $pass = $this->passes()->grant($this->owner, $this->starter);

        $this->passes()->stop($pass->id);

        $this->snapshot()->assertJsonPath('data.plan', null);
        $this->expectExceptionObject(new FreePassException('No running free pass has that id.'));
        $this->passes()->stop($pass->id);
    }

    #[Test]
    public function only_passes_are_listed(): void
    {
        Subscription::factory()->create(['owner_type' => Workspace::class, 'owner_id' => Workspace::create()->id, 'plan_id' => $this->starter->id]);
        $pass = $this->passes()->grant($this->owner, $this->starter);

        $this->assertSame([$pass->id], $this->passes()->all()->pluck('id')->all());
        $this->assertTrue($this->passes()->all()->first()->relationLoaded('owner'));
    }

    #[Test]
    public function an_owner_with_a_live_plan_is_not_given_a_pass(): void
    {
        Subscription::factory()->create(['owner_type' => $this->owner->getMorphClass(), 'owner_id' => $this->owner->id, 'plan_id' => $this->starter->id]);

        $this->expectExceptionObject(new FreePassException('They already have a plan.'));
        $this->passes()->grant($this->owner, $this->starter);
    }

    #[Test]
    public function a_second_pass_waits_for_the_first_to_stop(): void
    {
        $this->passes()->grant($this->owner, $this->starter);

        $this->expectExceptionObject(new FreePassException('They already have a free pass. Stop it first.'));
        $this->passes()->grant($this->owner, $this->starter);
    }

    #[Test]
    public function a_pass_cannot_end_before_it_starts(): void
    {
        $this->expectExceptionObject(new FreePassException('The end date has already passed.'));
        $this->passes()->grant($this->owner, $this->starter, now()->subDay());
    }
}
