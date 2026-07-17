<?php

namespace Whilesmart\Entitlements\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Per-owner override. Lets an owner carry a bespoke feature, cap or meter
 * allowance without a plan; PlanEntitlements consults overrides before the
 * plan, so an override always wins.
 */
class Entitlement extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'limit_value' => 'integer',
        'meter_allowance' => 'integer',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('entitlements.entitlements_table', 'entitlements');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
