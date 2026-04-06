<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WalletInvitation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:prune-expired-invitations')]
#[Description('Delete wallet invitations that have expired')]
class PruneExpiredInvitations extends Command
{
    public function handle(): void
    {
        $pruned = WalletInvitation::where('expires_at', '<', now())
            ->whereNull('accepted_at')
            ->whereNull('declined_at')
            ->delete();

        $this->info(sprintf('Pruned %d expired invitation(s).', $pruned));
    }
}
