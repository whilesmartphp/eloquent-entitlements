<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Contracts\UsageMeter;
use Whilesmart\Entitlements\Models\Entitlement;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * Resolves entitlements from the model layer: per-owner overrides win, then the
 * owner's active-or-comped subscription plan. A comped subscription with no
 * plan is treated as unlimited. Metering is delegated to the bound UsageMeter.
 */
class PlanEntitlements implements Entitlements
{
    public function __construct(private UsageMeter $meter) {}

    public function allows(?Model $owner, string $feature): bool
    {
        if ($owner === null) {
            return false;
        }

        foreach ($this->overrides($owner) as $override) {
            if ($override->feature === $feature) {
                return true;
            }
        }

        $plan = $this->activePlan($owner);

        if ($plan === null) {
            // An entitling subscription with no plan (a pure comp) is unlimited.
            return $this->activeSubscription($owner) !== null;
        }

        return $plan->feature($feature);
    }

    public function limit(?Model $owner, string $key): ?int
    {
        if ($owner === null) {
            return null;
        }

        foreach ($this->overrides($owner) as $override) {
            if ($override->limit_key === $key) {
                return $override->limit_value;
            }
        }

        $plan = $this->activePlan($owner);

        if ($plan === null) {
            return null;
        }

        return $plan->limitFor($key);
    }

    public function remaining(?Model $owner, string $meter): int|float
    {
        return $this->meter->remaining($owner, $meter, $this->meterAllowance($owner, $meter));
    }

    public function consume(?Model $owner, string $meter, int $amount): void
    {
        $this->meter->consume($owner, $meter, $amount);
    }

    private function meterAllowance(?Model $owner, string $meter): int|float
    {
        if ($owner === null) {
            return INF;
        }

        foreach ($this->overrides($owner) as $override) {
            if ($override->meter === $meter) {
                return $override->meter_allowance ?? INF;
            }
        }

        $plan = $this->activePlan($owner);

        if ($plan === null) {
            return INF;
        }

        return $plan->meterAllowance($meter) ?? INF;
    }

    private function activeSubscription(?Model $owner): ?Subscription
    {
        if ($owner === null) {
            return null;
        }

        $model = config('entitlements.models.subscription', Subscription::class);

        return $model::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->active()
            ->latest('id')
            ->first();
    }

    private function activePlan(?Model $owner): ?Plan
    {
        return $this->activeSubscription($owner)?->plan;
    }

    /**
     * @return Collection<int, Entitlement>
     */
    private function overrides(Model $owner)
    {
        $model = config('entitlements.models.entitlement', Entitlement::class);

        return $model::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->get();
    }
}
