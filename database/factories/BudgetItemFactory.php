<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BudgetItem>
 */
class BudgetItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'budget_id' => Budget::factory(),
            'type' => $this->faker->randomElement(['income', 'savings', 'bills', 'expenses', 'debt']),
            'label' => $this->faker->words(2, true),
            'planned_amount' => $this->faker->randomFloat(2, 50, 1500),
            'category_id' => null,
            'position' => 0,
        ];
    }
}
