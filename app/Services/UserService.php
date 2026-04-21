<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PlanType;
use App\Enums\UserRole;
use App\Mail\AccountDeletedMail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

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

    public function createForAdmin(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'plan' => PlanType::from($data['plan']),
            'locale' => $data['locale'],
            'currency' => $data['currency'],
        ]);

        $user->assignRole(UserRole::User->value);

        return $user;
    }

    public function updateForAdmin(User $user, array $data): void
    {
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'plan' => PlanType::from($data['plan']),
            'locale' => $data['locale'],
            'currency' => $data['currency'],
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        $user->update($attributes);
    }

    public function deleteUser(User $user): void
    {
        Mail::to($user->email)
            ->locale($user->locale ?? config('app.locale'))
            ->send(new AccountDeletedMail($user->name));

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
