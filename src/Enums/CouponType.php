<?php

namespace Whilesmart\Entitlements\Enums;

enum CouponType: string
{
    case Comp = 'comp';
    case PlanGrant = 'plan_grant';
    case PercentOff = 'percent_off';
    case FixedOff = 'fixed_off';

    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
