<?php

namespace Whilesmart\Entitlements\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trialing = 'trialing';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Comped = 'comped';

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    /**
     * Statuses that entitle the owner: paid, on trial, or comped.
     *
     * @return array<int, string>
     */
    public static function entitled(): array
    {
        return [self::Active->value, self::Trialing->value, self::Comped->value];
    }
}
