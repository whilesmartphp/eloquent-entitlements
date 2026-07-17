<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Whilesmart\Entitlements\Contracts\OwnerResolver;

/**
 * Default resolver: the authenticated user is the owner. Correct for a
 * user-scoped app; a host that gates by workspace or organization binds its own.
 */
class AuthUserOwnerResolver implements OwnerResolver
{
    public function resolve(Request $request): ?Model
    {
        $user = $request->user();

        return $user instanceof Model ? $user : null;
    }
}
