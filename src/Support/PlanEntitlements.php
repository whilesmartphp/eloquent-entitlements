<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Whilesmart\Entitlements\Contracts\Entitlements;
use Whilesmart\Entitlements\Contracts\PlanSource;
use Whilesmart\Entitlements\Contracts\UsageMeter;
use Whilesmart\Entitlements\Models\Entitlement;

/**
 * Applies the resolution rules: a per-owner override wins, otherwise whatever
 * plan the bound PlanSource resolves for the owner. Nothing here knows where
 * plans or subscriptions are stored; metering goes to the bound UsageMeter.
 */
class PlanEntitlements implements Entitlements
{
    public function __construct(
        private UsageMeter $meter,
        private PlanSource $plans,
    ) {}

    public function allows(?Model $owner, string $feature): bool
    {
        return $this->check($owner, $feature)->allowed;
    }

    public function check(?Model $owner, string $feature): AccessResult
    {
        if ($owner === null) {
            return AccessResult::deny($feature, 'no_owner');
        }

        foreach ($this->overrides($owner) as $override) {
            if ($override->feature === $feature) {
                return AccessResult::allow($feature);
            }
        }

        $plan = $this->plans->resolve($owner);

        if ($plan === null) {
            return AccessResult::deny($feature, 'no_plan');
        }

        return $plan->feature($feature)
            ? AccessResult::allow($feature)
            : AccessResult::deny($feature, 'feature_not_in_plan');
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

        return $this->plans->resolve($owner)?->limitFor($key);
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

        return $this->plans->resolve($owner)?->meterAllowance($meter) ?? INF;
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
