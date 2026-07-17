<?php

namespace Whilesmart\Entitlements\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Entitlements\Enums\SubscriptionStatus;
use Whilesmart\Entitlements\Models\Subscription;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'status' => SubscriptionStatus::Active->value,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ];
    }

    public function comped(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Comped->value,
            'current_period_end' => null,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Canceled->value,
        ]);
    }
}
