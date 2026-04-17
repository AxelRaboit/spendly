<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Goal>
 */
class GoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'wallet_id'     => null,
            'category_id'   => null,
            'name'          => $this->faker->words(2, true),
            'target_amount' => $this->faker->randomFloat(2, 500, 5000),
            'saved_amount'  => 0,
            'deadline'      => null,
            'color'         => '#6366f1',
        ];
    }
}
