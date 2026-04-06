<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletRole;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletInvitation;
use App\Models\WalletMember;
use App\Notifications\WalletInvitationNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WalletInvitationService
{
    public const int INVITATION_EXPIRY_DAYS = 7;

    public function invite(Wallet $wallet, User $invitedBy, string $email, WalletRole $role): WalletInvitation
    {
        if ($role === WalletRole::Owner) {
            throw new InvalidArgumentException(__('flash.invitation.cannot_invite_owner'));
        }

        if (WalletMember::where('wallet_id', $wallet->id)->whereHas('user', fn ($q) => $q->where('email', $email))->exists()) {
            throw new InvalidArgumentException(__('flash.invitation.already_member'));
        }

        if (WalletInvitation::where('wallet_id', $wallet->id)->where('email', $email)->pending()->exists()) {
            throw new InvalidArgumentException(__('flash.invitation.already_pending'));
        }

        return DB::transaction(function () use ($wallet, $invitedBy, $email, $role) {
            // Delete any old (expired/declined/accepted) invitations for this email to allow re-invite
            WalletInvitation::where('wallet_id', $wallet->id)->where('email', $email)->delete();

            $invitation = WalletInvitation::create([
                'wallet_id' => $wallet->id,
                'invited_by' => $invitedBy->id,
                'email' => $email,
                'role' => $role,
                'token' => Str::random(64),
                'expires_at' => now()->addDays(self::INVITATION_EXPIRY_DAYS),
            ]);

            Notification::route('mail', $email)->notify(new WalletInvitationNotification($invitation, $invitedBy, $wallet));

            return $invitation;
        });
    }

    public function findByToken(string $token): WalletInvitation
    {
        return WalletInvitation::where('token', $token)->with('wallet', 'inviter')->firstOrFail();
    }

    public function findPendingByToken(string $token): WalletInvitation
    {
        return WalletInvitation::where('token', $token)->pending()->firstOrFail();
    }

    public function findFirstPendingForEmail(string $email): ?WalletInvitation
    {
        return WalletInvitation::where('email', $email)->pending()->first();
    }

    public function listPendingForWallet(Wallet $wallet): Collection
    {
        return WalletInvitation::where('wallet_id', $wallet->id)->pending()->get(['id', 'email', 'role', 'expires_at']);
    }

    public function acceptForUser(string $token, User $user): WalletMember
    {
        $invitation = $this->findPendingByToken($token);

        if ($user->email !== $invitation->email) {
            throw new InvalidArgumentException(__('flash.invitation.wrong_email'));
        }

        return $this->accept($invitation);
    }

    public function accept(WalletInvitation $invitation): WalletMember
    {
        $user = User::where('email', $invitation->email)->firstOrFail();

        $member = WalletMember::firstOrCreate([
            'wallet_id' => $invitation->wallet_id,
            'user_id' => $user->id,
        ], [
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        return $member;
    }

    public function decline(WalletInvitation $invitation): void
    {
        $invitation->update(['declined_at' => now()]);
    }

    public function revoke(WalletInvitation $invitation): void
    {
        $invitation->delete();
    }

    public function resend(WalletInvitation $invitation, User $invitedBy, Wallet $wallet): WalletInvitation
    {
        $email = $invitation->email;
        $role = $invitation->role;
        $invitation->delete();

        return $this->invite($wallet, $invitedBy, $email, $role);
    }
}
