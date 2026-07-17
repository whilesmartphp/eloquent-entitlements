<?php

namespace Whilesmart\Entitlements;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Whilesmart\Entitlements\Contracts\BillingProvider;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Contracts\OwnerResolver;
use Whilesmart\Entitlements\Contracts\PlanSource;
use Whilesmart\Entitlements\Contracts\UsageMeter;
use Whilesmart\Entitlements\Http\Middleware\RequireFeature;
use Whilesmart\Entitlements\Support\AllowAllEntitlements;
use Whilesmart\Entitlements\Support\AuthUserOwnerResolver;
use Whilesmart\Entitlements\Support\EloquentPlanSource;
use Whilesmart\Entitlements\Support\NullBillingProvider;
use Whilesmart\Entitlements\Support\NullUsageMeter;

class EntitlementsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/entitlements.php', 'entitlements');

        // Permissive default so a self-host stays free until it opts to enforce.
        $this->app->singleton(Entitlements::class, AllowAllEntitlements::class);
        $this->app->bindIf(UsageMeter::class, NullUsageMeter::class);
        $this->app->bindIf(BillingProvider::class, NullBillingProvider::class);
        $this->app->bindIf(PlanSource::class, EloquentPlanSource::class);
        $this->app->bindIf(OwnerResolver::class, AuthUserOwnerResolver::class);
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('feature', RequireFeature::class);

        // A host that binds its own PlanSource may not want this package's
        // tables at all; let it opt out and keep only the contract.
        if (config('entitlements.register_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        $this->publishes([
            __DIR__.'/../config/entitlements.php' => config_path('entitlements.php'),
        ], 'entitlements-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'entitlements-migrations');

        if (config('entitlements.register_routes', true)) {
            Route::middleware(config('entitlements.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('entitlements.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
