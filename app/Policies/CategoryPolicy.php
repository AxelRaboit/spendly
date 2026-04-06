<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Models\Wallet;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return Wallet::find($category->wallet_id)?->roleFor($user) !== null;
    }

    public function update(User $user, Category $category): bool
    {
        return Wallet::find($category->wallet_id)?->roleFor($user)?->canEdit() ?? false;
    }

    public function delete(User $user, Category $category): bool
    {
        return Wallet::find($category->wallet_id)?->roleFor($user)?->canEdit() ?? false;
    }
}
