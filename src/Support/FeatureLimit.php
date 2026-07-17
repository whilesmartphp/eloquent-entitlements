<?php

namespace Whilesmart\Entitlements\Support;

/**
 * A numeric limit and how much of it is spent. A null limit is unlimited (never
 * exhausted); this package uses null throughout rather than a -1 sentinel.
 */
final class FeatureLimit
{
    public function __construct(
        public readonly string $key,
        public readonly ?int $limit,
        public readonly int|float $used = 0,
        public readonly int|float $remaining = INF,
    ) {}

    public function isUnlimited(): bool
    {
        return $this->limit === null;
    }

    public function isExhausted(): bool
    {
        return ! $this->isUnlimited() && $this->remaining <= 0;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'limit' => $this->limit,
            'used' => $this->used,
            'remaining' => $this->isUnlimited() ? null : $this->remaining,
        ];
    }
}
