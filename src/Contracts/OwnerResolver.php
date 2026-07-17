<?php

namespace Whilesmart\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Turns a request into the owner a gate is checked against. Which model owns
 * entitlements is the host's decision: the authenticated user, their current
 * workspace, an organization from the route. The middleware needs this seam so
 * it never hard-codes one shape.
 */
interface OwnerResolver
{
    public function resolve(Request $request): ?Model;
}
