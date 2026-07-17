<?php

namespace Whilesmart\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * Billing seam. The package is provider-neutral and never imports a payment
 * SDK: the host binds an adapter (Stripe, Paddle, etc.) that fulfils this.
 * The default is a no-op that refuses checkout.
 */
interface BillingProvider
{
    /**
     * Start a checkout for $owner against $plan and return a redirect URL.
     */
    public function createCheckout(Model $owner, Plan $plan): string;

    /**
     * Reconcile local subscription state from a provider webhook payload.
     */
    public function syncFromWebhook(array $payload): void;

    /**
     * Cancel $subscription with the provider.
     */
    public function cancel(Subscription $subscription): void;
}
