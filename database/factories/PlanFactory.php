<?php

namespace Whilesmart\Entitlements\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Whilesmart\Entitlements\Models\Plan;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(2, true),
            'features' => ['reports' => true, 'export' => false],
            'limits' => ['seats' => 5, 'projects' => null],
            'meters' => ['api_calls' => 1000],
        ];
    }

    public function unlimited(): static
    {
        return $this->state(fn () => [
            'features' => ['reports' => true, 'export' => true],
            'limits' => ['seats' => null, 'projects' => null],
            'meters' => [],
        ]);
    }
}
