<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlanType;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-trials')]
#[Description('Downgrade users whose Pro trial has expired')]
class ExpireTrials extends Command
{
    public function handle(): void
    {
        $expired = User::query()
            ->where('plan', PlanType::Pro)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->get();

        foreach ($expired as $user) {
            $user->update([
                'plan' => PlanType::Free,
                'trial_ends_at' => null,
            ]);
            $this->line(sprintf('Trial expired: %s (%s)', $user->name, $user->email));
        }

        $this->info(sprintf('Done. %d trial(s) expired.', $expired->count()));
    }
}
