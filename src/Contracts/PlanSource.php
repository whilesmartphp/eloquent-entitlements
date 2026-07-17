<?php

namespace Whilesmart\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Support\ResolvedPlan;

/**
 * Where an owner's plan comes from. The package ships an Eloquent source over
 * its own subscriptions and plans tables, but a host may bind any other: plans
 * declared in config, held by a billing provider, or served by a remote
 * subscriptions service.
 *
 * Resolving the owner's subscription is this seam's business, not the caller's,
 * so an app that models subscriptions differently (or does not model them at
 * all) only replaces this.
 */
interface PlanSource
{
    /**
     * The owner's plan, or null when nothing entitles them.
     *
     * Return ResolvedPlan::unlimited() for an entitling subscription that
     * carries no plan, which is how a pure comp reads.
     */
    public function resolve(?Model $owner): ?ResolvedPlan;
}
