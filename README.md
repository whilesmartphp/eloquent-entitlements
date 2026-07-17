# whilesmart/eloquent-entitlements

A provider-neutral entitlements layer for Laravel: feature flags, plan limits and
metered usage, all scoped to a polymorphic owner (a workspace, organization, user)
through [`whilesmart/eloquent-owner-access`](https://github.com/whilesmartphp/eloquent-owner-access).

The package ships an allow-all default, so a self-host stays fully unlocked and free.
A host that wants to charge rebinds one interface to enforce plans. No payment SDK is
imported anywhere; billing is a seam the host fills.

## Install

```
composer require whilesmart/eloquent-entitlements
php artisan migrate
```

Routes register automatically under the `api` prefix with `auth:sanctum`. Set
`ENTITLEMENTS_REGISTER_ROUTES=false` to mount them yourself.

## The gating seam

Everything gates through one contract, `Whilesmart\Entitlements\Contracts\Entitlements`:

```php
$entitlements = app(Entitlements::class);

$entitlements->allows($owner, 'reports');      // bool
$entitlements->limit($owner, 'seats');         // ?int, null = unlimited
$entitlements->remaining($owner, 'api_calls'); // int|float
$entitlements->consume($owner, 'api_calls', 1);
```

The default binding, `AllowAllEntitlements`, allows every feature, imposes no limit and
never meters. To enforce plans, rebind it to `PlanEntitlements` in a service provider:

```php
$this->app->bind(Entitlements::class, PlanEntitlements::class);
```

## Owning model

Add the trait to whatever owns entitlements:

```php
use Whilesmart\Entitlements\Traits\HasEntitlements;

class Workspace extends Model
{
    use HasEntitlements;
}

$workspace->entitledTo('reports'); // delegates to the bound Entitlements
$workspace->limitFor('seats');
$workspace->activeSubscription();
```

## Model layer

- `Plan`: a `key`, `features` (map of feature to bool), `limits` (map to int or null),
  `meters` (map to a monthly allowance), and an optional `provider_price_id`.
- `Subscription`: the owner's plan and lifecycle status (`active`, `trialing`, `past_due`,
  `canceled`, `comped`). The `active()` scope selects entitling subscriptions.
- `Entitlement`: a per-owner override for a single feature, limit or meter. Overrides win
  over the plan, so an owner can carry a bespoke cap without one.
- `Coupon` / `CouponRedemption`: comp, plan-grant and discount coupons, with every
  redemption recorded for audit.

`PlanEntitlements` resolves the four contract methods from this layer: overrides first,
then the owner's active-or-comped subscription plan. A comped subscription with no plan
reads as unlimited.

## Metering

`remaining` and `consume` delegate to a `UsageMeter` the host binds. The default,
`NullUsageMeter`, reports unlimited and records nothing, so the package never owns a
usage table. Bind your own to count real usage:

```php
$this->app->bind(UsageMeter::class, MyRedisMeter::class);
```

## Coupons

```php
app(CouponService::class)->redeem($coupon, $owner);
```

- `comp` / `plan_grant` create a `comped` subscription against the granted plan. A comp
  with `bypass` (or no plan) yields an unlimited comped subscription.
- `percent_off` / `fixed_off` record a per-owner entitlement carrying the discount for the
  host's billing adapter to apply at checkout. No charge happens in the package.

Redemption is rejected when the coupon is expired or has reached `max_redemptions`.

## Billing

`Whilesmart\Entitlements\Contracts\BillingProvider` is the checkout seam. The default,
`NullBillingProvider`, refuses checkout and no-ops the rest. A host binds a real adapter
(Stripe, Paddle, etc.); the package stays provider-neutral and imports no payment SDK.

## Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/api/entitlements` | Owner's current snapshot (plan, features, limits) |
| POST | `/api/entitlements/redeem-coupon` | Redeem a coupon by `code` for the owner |

Both are owner-scoped through owner-access: a caller can only read or redeem into an owner
the bound authorizer permits.

## Configuration

`config/entitlements.php` exposes `register_routes`, `route_prefix`, `route_middleware`,
the five table names, and a `models` map so any model can be swapped for a host subclass.
