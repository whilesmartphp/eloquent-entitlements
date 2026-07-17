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

    'plans_table' => env('ENTITLEMENTS_PLANS_TABLE', 'plans'),
    'subscriptions_table' => env('ENTITLEMENTS_SUBSCRIPTIONS_TABLE', 'subscriptions'),
    'entitlements_table' => env('ENTITLEMENTS_ENTITLEMENTS_TABLE', 'entitlements'),
    'coupons_table' => env('ENTITLEMENTS_COUPONS_TABLE', 'coupons'),
    'coupon_redemptions_table' => env('ENTITLEMENTS_COUPON_REDEMPTIONS_TABLE', 'coupon_redemptions'),

    // Swap any model for a host subclass without touching the package.
    'models' => [
        'plan' => Plan::class,
        'subscription' => Subscription::class,
        'entitlement' => Entitlement::class,
        'coupon' => Coupon::class,
        'coupon_redemption' => CouponRedemption::class,
    ],
];
