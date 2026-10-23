<?php

namespace Whilesmart\Entitlements\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Whilesmart\Entitlements\Enums\SubscriptionStatus;
use Whilesmart\Entitlements\Exceptions\FreePassException;
use Whilesmart\Entitlements\Models\Plan;
use Whilesmart\Entitlements\Models\Subscription;

/**
 * A plan given to an owner without payment, until a date or until stopped.
 *
 * A pass is a comped subscription, so the plan resolves exactly as a paid one
 * does, and an end date stops it without a scheduled job: an expired period no
 * longer counts as active.
 *
 * An owner who already has a live plan cannot be given one. A billing adapter
 * writes the provider's state onto the owner's latest subscription, which would
 * then be the pass.
 */
class FreePassService
{
    /**
     * @throws FreePassException
     */
    public function grant(Model $owner, Plan $plan, ?Carbon $until = null): Subscription
    {
        if ($until?->isPast()) {
            throw new FreePassException('The end date has already passed.');
        }

        $current = $this->subscriptionModel()::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey())
            ->active()
            ->latest('id')
            ->first();

        if ($current !== null) {
            throw new FreePassException(! empty($current->metadata['free_pass'])
                ? 'They already have a free pass. Stop it first.'
                : 'They already have a plan.');
        }

        return $this->subscriptionModel()::create([
            'owner_type' => $owner->getMorphClass(),
            'owner_id' => $owner->getKey(),
            'plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Comped->value,
            'current_period_start' => now(),
            'current_period_end' => $until,
            'metadata' => ['free_pass' => true],
        ]);
    }

    /** @return Collection<int, Subscription> Newest first, with owner and plan. */
    public function all(): Collection
    {
        return $this->subscriptionModel()::query()
            ->where('metadata->free_pass', true)
            ->with(['owner', 'plan'])
            ->latest('id')
            ->get();
    }

    /**
     * End a pass at once.
     *
     * @throws FreePassException
     */
    public function stop(int|string $id): void
    {
        $pass = $this->subscriptionModel()::query()
            ->whereKey($id)
            ->where('metadata->free_pass', true)
            ->first();

        if ($pass === null || $pass->status !== SubscriptionStatus::Comped) {
            throw new FreePassException('No running free pass has that id.');
        }

        $pass->update([
            'status' => SubscriptionStatus::Canceled->value,
            'current_period_end' => now(),
        ]);
    }

    /** Whether a subscription is a pass still giving its plan. */
    public function running(Subscription $pass): bool
    {
        return ! empty($pass->metadata['free_pass'])
            && $pass->status === SubscriptionStatus::Comped
            && $pass->isEntitling();
    }

    /** @return class-string<Subscription> */
    private function subscriptionModel(): string
    {
        return config('entitlements.models.subscription', Subscription::class);
    }
}
