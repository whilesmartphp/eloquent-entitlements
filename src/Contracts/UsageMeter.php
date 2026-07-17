<?php

namespace Whilesmart\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Metered-usage backend. The package never owns a usage table: it resolves the
 * allowance for a meter from the owner's plan or overrides, then delegates the
 * counting to whatever the host binds. The default returns unlimited.
 */
interface UsageMeter
{
    /**
     * Units left for $meter given the resolved $allowance (INF when unlimited).
     */
    public function remaining(?Model $owner, string $meter, int|float $allowance): int|float;

    /**
     * Record $amount units consumed against $meter.
     */
    public function consume(?Model $owner, string $meter, int $amount): void;
}
