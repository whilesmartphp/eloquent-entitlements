<?php

namespace Whilesmart\Entitlements\Contracts;

use Illuminate\Database\Eloquent\Model;
use Whilesmart\Entitlements\Support\AccessResult;

/**
 * Gating seam for features, limits and metered usage, keyed on the resource
 * owner so a shared owner (a workspace or couple) is gated as a unit.
 *
 * The package binds a permissive default that allows everything; a host may
 * rebind it to enforce plan limits. No billing logic lives here.
 */
interface Entitlements
{
    public function allows(?Model $owner, string $feature): bool;

    /**
     * Like allows(), but returns why access was denied so a caller can act on
     * the reason (tell the user, choose an upgrade path) rather than a bare bool.
     */
    public function check(?Model $owner, string $feature): AccessResult;

    public function limit(?Model $owner, string $key): ?int;

    public function remaining(?Model $owner, string $meter): int|float;

    public function consume(?Model $owner, string $meter, int $amount): void;
}
