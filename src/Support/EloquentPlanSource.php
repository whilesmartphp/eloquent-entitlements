<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Contracts\PlanSource;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * Reads the owner's plan from this package's own subscriptions and plans
 * tables. The default source, and the reason those models exist; a host that
 * keeps plans elsewhere binds its own PlanSource and leaves these tables empty.
 */
class EloquentPlanSource implements PlanSource
{
    public function resolve(?Model $owner): ?ResolvedPlan
    {
        $subscription = $this->activeSubscription($owner);

        if ($subscription === null) {
            return null;
        }

        $plan = $subscription->plan;

        if ($plan === null) {
            // An entitling subscription with no plan is a pure comp.
            return ResolvedPlan::unlimited();
        }

        return ResolvedPlan::of(
            $plan->features ?? [],
            $plan->limits ?? [],
            $plan->meters ?? [],
        );
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
}
