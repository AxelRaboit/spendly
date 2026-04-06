<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\WalletRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    public function definition(): array
    {
        $names = ['Compte courant', 'Livret A', 'Compte joint', 'Cash', 'Carte prépayée'];

        return [
            'user_id' => User::factory(),
            'name' => $this->faker->randomElement($names),
            'start_balance' => $this->faker->randomFloat(2, 500, 5000),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Wallet $wallet) {
            WalletMember::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'role' => WalletRole::Owner,
            ]);
        });
    }
}
