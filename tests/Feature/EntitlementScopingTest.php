<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\Support\OnlyOwnerAuthorizer;
use Tests\Support\Workspace;
use Tests\TestCase;
use Whilesmart\Entitlements\Models\Coupon;
use Whilesmart\OwnerAccess\Contracts\OwnerAuthorizer;

class EntitlementScopingTest extends TestCase
{
    private const OWNER = Workspace::class;

    private Workspace $mine;

    private Workspace $theirs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mine = Workspace::create(['name' => 'Mine']);
        $this->theirs = Workspace::create(['name' => 'Theirs']);

        $this->app->instance(
            OwnerAuthorizer::class,
            new OnlyOwnerAuthorizer([(string) $this->mine->id]),
        );
    }

    #[Test]
    public function an_owner_cannot_read_another_owners_snapshot(): void
    {
        $this->getJson('/api/entitlements?'.http_build_query([
            'owner_type' => self::OWNER,
            'owner_id' => $this->theirs->id,
        ]))->assertForbidden();
    }

    #[Test]
    public function an_owner_can_read_its_own_snapshot(): void
    {
        $this->getJson('/api/entitlements?'.http_build_query([
            'owner_type' => self::OWNER,
            'owner_id' => $this->mine->id,
        ]))->assertOk();
    }

    #[Test]
    public function an_owner_cannot_redeem_into_another_owner(): void
    {
        $coupon = Coupon::factory()->comp()->create(['code' => 'FREE']);

        $this->postJson('/api/entitlements/redeem-coupon', [
            'owner_type' => self::OWNER,
            'owner_id' => $this->theirs->id,
            'code' => 'FREE',
        ])->assertForbidden();

        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('coupon_redemptions', 0);
    }
}
