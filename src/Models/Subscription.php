<?php

namespace Whilesmart\Entitlements\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Whilesmart\Entitlements\Database\Factories\SubscriptionFactory;
use Whilesmart\Entitlements\Enums\SubscriptionStatus;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => SubscriptionStatus::class,
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('entitlements.subscriptions_table', 'entitlement_subscriptions');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(config('entitlements.models.plan', Plan::class), 'plan_id');
    }

    /**
     * Entitling subscriptions: active, trialing or comped, and not past their
     * current period end. A comped subscription with no plan is unlimited.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', SubscriptionStatus::entitled())
            ->where(function (Builder $q) {
                $q->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>=', now());
            });
    }

    public function isEntitling(): bool
    {
        if (! in_array($this->status?->value, SubscriptionStatus::entitled(), true)) {
            return false;
        }

        return $this->current_period_end === null || $this->current_period_end->gte(now());
    }

    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
    }
}
