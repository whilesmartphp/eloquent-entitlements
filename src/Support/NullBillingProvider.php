<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Whilesmart\Entitlements\Contracts\BillingProvider;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * Default billing provider: no charges, no external calls. Checkout is refused
 * so a self-host never silently pretends to bill; sync and cancel are no-ops.
 * The host binds a real adapter to take payments.
 */
class NullBillingProvider implements BillingProvider
{
    public function createCheckout(Model $owner, Plan $plan): string
    {
        throw new RuntimeException('No billing provider is configured. Bind a BillingProvider to enable checkout.');
    }

    public function syncFromWebhook(array $payload): void {}

    public function cancel(Subscription $subscription): void {}
}
