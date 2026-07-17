<?php

namespace Whilesmart\Entitlements\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Entitlements\Enums\CouponType;
use Whilesmart\Entitlements\Models\Coupon;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->bothify('SAVE-####')),
            'type' => CouponType::PlanGrant->value,
            'bypass' => false,
        ];
    }

    public function comp(): static
    {
        return $this->state(fn () => [
            'type' => CouponType::Comp->value,
            'bypass' => true,
        ]);
    }

    public function percentOff(int $amount = 20): static
    {
        return $this->state(fn () => [
            'type' => CouponType::PercentOff->value,
            'amount' => $amount,
        ]);
    }
}
