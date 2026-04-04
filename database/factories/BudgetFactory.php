<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'month' => $this->faker->dateTimeBetween('-3 months', 'now')->format('Y-m-01'),
        ];
    }
}
