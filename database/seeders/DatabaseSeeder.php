<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = collect([
            ['name' => 'Alice Dupont', 'email' => 'alice@example.com'],
            ['name' => 'Bob Martin', 'email' => 'bob@example.com'],
            ['name' => 'Test User', 'email' => 'test@example.com'],
        ])->map(fn ($data) => User::factory()->create([
            ...$data,
            'password' => bcrypt('password'),
        ]));

        foreach ($users as $user) {
            $categories = Category::factory(12)->create(['user_id' => $user->id]);

            Transaction::factory(60)->make()->each(function ($transaction) use ($user, $categories) {
                $transaction->user_id = $user->id;
                $transaction->category_id = $categories->random()->id;
                $transaction->save();
            });
        }
    }
}
