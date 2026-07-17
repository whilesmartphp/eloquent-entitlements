<?php

namespace Whilesmart\Entitlements\Support;

/**
 * An owner's plan, flattened away from wherever it was stored. A PlanSource
 * returns one of these so the resolution rules never depend on plans living in
 * this package's tables.
 *
 * Keys are read with data_get, so a source may nest them ('reports.export').
 */
final class ResolvedPlan
{
    private function __construct(
        public readonly bool $unlimited,
        private readonly array $features,
        private readonly array $limits,
        private readonly array $meters,
    ) {}

    /**
     * Entitled to everything: a comp with no plan behind it.
     */
    public static function unlimited(): self
    {
        return new self(true, [], [], []);
    }

    public static function of(array $features = [], array $limits = [], array $meters = []): self
    {
        return new self(false, $features, $limits, $meters);
    }

    public function feature(string $feature): bool
    {
        return $this->unlimited || (bool) data_get($this->features, $feature, false);
    }

    public function limitFor(string $key): ?int
    {
        if ($this->unlimited) {
            return null;
        }

        $value = data_get($this->limits, $key);

        return $value === null ? null : (int) $value;
    }

    public function meterAllowance(string $meter): ?int
    {
        if ($this->unlimited) {
            return null;
        }

        $value = data_get($this->meters, $meter);

        return $value === null ? null : (int) $value;
    }
}
