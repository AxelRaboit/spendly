<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
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
        ];
    }
}
