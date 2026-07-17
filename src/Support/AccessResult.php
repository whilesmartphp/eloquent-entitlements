<?php

namespace Whilesmart\Entitlements\Support;

/**
 * The outcome of a gate check, carrying why it failed so a caller can tell the
 * user something better than "denied". A bare bool cannot say "you are on the
 * free plan, upgrade for this".
 */
final class AccessResult
{
    private function __construct(
        public readonly bool $allowed,
        public readonly string $feature,
        public readonly ?string $reason = null,
        public readonly ?FeatureLimit $limit = null,
    ) {}

    public static function allow(string $feature, ?FeatureLimit $limit = null): self
    {
        return new self(true, $feature, null, $limit);
    }

    public static function deny(string $feature, string $reason, ?FeatureLimit $limit = null): self
    {
        return new self(false, $feature, $reason, $limit);
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'feature' => $this->feature,
            'reason' => $this->reason,
            'limit' => $this->limit?->toArray(),
        ];
    }
}
