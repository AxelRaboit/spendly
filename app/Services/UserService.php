<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function register(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'plan' => PlanType::Pro,
            'trial_ends_at' => now()->addDays(30),
        ]);
    }
}
