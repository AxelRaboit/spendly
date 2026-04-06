<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'plan' => PlanType::Pro,
            'trial_ends_at' => now()->addDays(30),
        ]);

        $user->assignRole(UserRole::User->value);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function toggleRole(User $user): void
    {
        if ($user->hasRole(UserRole::Dev->value)) {
            $user->removeRole(UserRole::Dev->value);
            $user->assignRole(UserRole::User->value);
        } else {
            $user->removeRole(UserRole::User->value);
            $user->assignRole(UserRole::Dev->value);
        }
    }

    /**
     * @param  array{search?: string}  $filters
     */
    public function searchForAdmin(array $filters): LengthAwarePaginator
    {
        $query = User::query();

        if ($filters['search'] ?? null) {
            $search = $filters['search'];
            $query->where('name', 'like', sprintf('%%%s%%', $search))
                ->orWhere('email', 'like', sprintf('%%%s%%', $search));
        }

        return $query->with('roles')->paginate(15)->withQueryString();
    }
}
