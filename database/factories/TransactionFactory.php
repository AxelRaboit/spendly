<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => null,
            'category_id' => Category::factory(),
            'type' => $this->faker->randomElement(['expense', 'income']),
            'amount' => $this->faker->randomFloat(2, 1, 500),
            'description' => $this->faker->optional(0.7)->sentence(3),
            'date' => $this->faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'tags' => null,
        ];
    }
}
