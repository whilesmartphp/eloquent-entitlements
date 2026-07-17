<?php

namespace Whilesmart\Entitlements\Support;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Contracts\UsageMeter;

/**
 * Default meter: everything is unlimited and consumption is not recorded, so
 * the package never needs a usage table. The host binds a real meter to count.
 */
class NullUsageMeter implements UsageMeter
{
    public function remaining(?Model $owner, string $meter, int|float $allowance): int|float
    {
        return INF;
    }

    public function consume(?Model $owner, string $meter, int $amount): void {}
}
