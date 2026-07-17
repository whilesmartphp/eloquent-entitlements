<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Whilesmart\Entitlements\EntitlementsServiceProvider;
use Whilesmart\OwnerAccess\OwnerAccessServiceProvider;

/**
 * The package's own migration loading, checked without the base TestCase, which
 * always loads the migrations itself through defineDatabaseMigrations.
 */
class RegisterMigrationsTest extends BaseTestCase
{
    private bool $register = true;

    protected function getPackageProviders($app): array
    {
        return [
            OwnerAccessServiceProvider::class,
            EntitlementsServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('entitlements.register_migrations', $this->register);
    }

    #[Test]
    public function tables_are_created_by_default(): void
    {
        $this->register = true;
        $this->refreshApplication();
        $this->artisan('migrate')->run();

        $this->assertTrue(Schema::hasTable('entitlement_plans'));
        $this->assertTrue(Schema::hasTable('entitlement_subscriptions'));
    }

    #[Test]
    public function tables_are_skipped_when_the_host_opts_out(): void
    {
        $this->register = false;
        $this->refreshApplication();
        $this->artisan('migrate')->run();

        $this->assertFalse(Schema::hasTable('entitlement_plans'));
        $this->assertFalse(Schema::hasTable('entitlement_subscriptions'));
    }
}
