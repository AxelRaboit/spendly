<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletMemberService
{
    public function listMembers(Wallet $wallet): Collection
    {
        return $wallet->members()->with('user:id,name,email')->get();
    }

    public function findMember(Wallet $wallet, User $user): WalletMember
    {
        return WalletMember::where('wallet_id', $wallet->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function removeMember(WalletMember $member): void
    {
        if ($member->role === WalletRole::Owner) {
            throw new InvalidArgumentException(__('flash.invitation.cannot_remove_owner'));
        }

        $member->delete();
    }

    public function transferOwnership(Wallet $wallet, WalletMember $currentOwner, WalletMember $newOwner): void
    {
        if ($currentOwner->role !== WalletRole::Owner) {
            throw new InvalidArgumentException(__('flash.invitation.not_owner'));
        }

        DB::transaction(function () use ($wallet, $currentOwner, $newOwner) {
            $newOwner->update(['role' => WalletRole::Owner]);
            $currentOwner->update(['role' => WalletRole::Editor]);
            $wallet->update(['user_id' => $newOwner->user_id]);
        });
    }

    public function updateRole(WalletMember $member, WalletRole $newRole): void
    {
        if ($member->role === WalletRole::Owner || $newRole === WalletRole::Owner) {
            throw new InvalidArgumentException(__('flash.invitation.cannot_change_owner'));
        }

        $member->update(['role' => $newRole]);
    }
}
