<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Models\WalletInvitation;
use Illuminate\Auth\Events\Registered;

class ClaimPendingInvitations
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->user;

        // Mark pending invitations as visible but don't auto-accept.
        // The user will see the pendingInvitations count in the UI and can
        // accept or decline from the invitation page.
        WalletInvitation::where('email', $user->email)->pending()->exists();
    }
}
