<?php

use Whilesmart\Entitlements\Models\Coupon;
use Whilesmart\Entitlements\Models\CouponRedemption;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

return [
    'register_routes' => env('ENTITLEMENTS_REGISTER_ROUTES', true),
    'route_prefix' => env('ENTITLEMENTS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],

    // Namespaced so a host can install this alongside a billing package that
    // owns the generic names. Laravel Cashier hardcodes `subscriptions` in its
    // published migration, so an unprefixed default would collide with it.
    'plans_table' => env('ENTITLEMENTS_PLANS_TABLE', 'entitlement_plans'),
    'subscriptions_table' => env('ENTITLEMENTS_SUBSCRIPTIONS_TABLE', 'entitlement_subscriptions'),
    'entitlements_table' => env('ENTITLEMENTS_ENTITLEMENTS_TABLE', 'entitlements'),
    'coupons_table' => env('ENTITLEMENTS_COUPONS_TABLE', 'entitlement_coupons'),
    'coupon_redemptions_table' => env('ENTITLEMENTS_COUPON_REDEMPTIONS_TABLE', 'entitlement_coupon_redemptions'),

    // Swap any model for a host subclass without touching the package.
    'models' => [
        'plan' => Plan::class,
        'subscription' => Subscription::class,
        'entitlement' => Entitlement::class,
        'coupon' => Coupon::class,
        'coupon_redemption' => CouponRedemption::class,
    ],
];
