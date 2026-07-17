<?php

namespace Tests\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Contracts\PlanSource;
use Whilesmart\Entitlements\Support\ResolvedPlan;

/**
 * A plan source backed by a plain array, standing in for a host that declares
 * plans in config or fetches them from a remote service. It touches no
 * subscriptions or plans table.
 */
class ArrayPlanSource implements PlanSource
{
    /**
     * @param  array<int|string, ?ResolvedPlan>  $plans  keyed by owner id
     */
    public function __construct(private array $plans = []) {}

    public function resolve(?Model $owner): ?ResolvedPlan
    {
        if ($owner === null) {
            return null;
        }

        return $this->plans[$owner->getKey()] ?? null;
    }
}
