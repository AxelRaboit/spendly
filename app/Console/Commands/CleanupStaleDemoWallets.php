<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Wallet;
use App\Services\TourSeederService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:cleanup-stale-demo-wallets')]
#[Description('Remove demo wallets and their related data for users who did not complete the onboarding tour')]
class CleanupStaleDemoWallets extends Command
{
    public function __construct(private readonly TourSeederService $seeder)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $wallets = Wallet::where('is_demo', true)->with('user')->get();

        foreach ($wallets as $wallet) {
            if (! $wallet->user instanceof User) {
                continue;
            }

            $this->seeder->cleanup($wallet->user);
        }

        $this->info(sprintf('Cleaned up %d stale demo wallet(s).', $wallets->count()));
    }
}
