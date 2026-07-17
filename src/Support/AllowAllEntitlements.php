<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Contracts\Entitlements;

/**
 * Default Entitlements: grants every feature, imposes no limit, never meters.
 * Keeps the open core free and self-hostable until a host overrides it.
 */
class AllowAllEntitlements implements Entitlements
{
    public function allows(?Model $owner, string $feature): bool
    {
        return true;
    }

    public function check(?Model $owner, string $feature): AccessResult
    {
        return AccessResult::allow($feature);
    }

    public function limit(?Model $owner, string $key): ?int
    {
        return null;
    }

    public function remaining(?Model $owner, string $meter): int|float
    {
        return INF;
    }

    public function consume(?Model $owner, string $meter, int $amount): void {}
}
