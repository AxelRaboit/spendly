<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $categories = [
            'Alimentation', 'Transport', 'Logement', 'Santé', 'Loisirs',
            'Vêtements', 'Restaurants', 'Abonnements', 'Voyages', 'Sport',
            'Culture', 'Éducation', 'Cadeaux', 'Épargne', 'Électronique',
        ];

        return [
            'name' => $this->faker->randomElement($categories),
            'user_id' => User::factory(),
            'wallet_id' => fn (array $attrs) => Wallet::factory()->create(['user_id' => $attrs['user_id']])->id,
        ];
    }
}
