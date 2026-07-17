<?php

namespace Whilesmart\Entitlements\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whilesmart\Entitlements\Database\Factories\PlanFactory;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'meters' => 'array',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('entitlements.plans_table', 'plans');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(config('entitlements.models.subscription', Subscription::class), 'plan_id');
    }

    public function feature(string $feature): bool
    {
        return (bool) data_get($this->features, $feature, false);
    }

    public function limitFor(string $key): ?int
    {
        $value = data_get($this->limits, $key);

        return $value === null ? null : (int) $value;
    }

    public function meterAllowance(string $meter): ?int
    {
        $value = data_get($this->meters, $meter);

        return $value === null ? null : (int) $value;
    }

    protected static function newFactory(): PlanFactory
    {
        return PlanFactory::new();
    }
}
