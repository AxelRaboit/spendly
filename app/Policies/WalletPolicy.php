<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WalletRole;
use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return $wallet->roleFor($user) instanceof WalletRole;
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $wallet->roleFor($user)?->canEdit() ?? false;
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        return $wallet->roleFor($user)?->canDelete() ?? false;
    }

    public function manageMembers(User $user, Wallet $wallet): bool
    {
        return $wallet->roleFor($user)?->canManageMembers() ?? false;
    }
}
