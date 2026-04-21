<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DemoFixtureService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('spendly:seed-fixtures {--email= : Email of the user to reset} {--force : Skip confirmation prompt}')]
#[Description('Reset a user\'s data and replace it with demo fixture data')]
class SeedUserFixtures extends Command
{
    public function handle(DemoFixtureService $fixtures): int
    {
        $email = $this->option('email') ?? $this->ask('User email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error(sprintf('No user found with email [%s].', $email));

            return 1;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('This will DELETE all data for [%s] and replace it with fixtures. Continue?', $email))) {
            $this->info('Aborted.');

            return 0;
        }

        $this->line(sprintf('  Clearing data for <comment>%s</comment>...', $email));
        $fixtures->clear($user);

        $this->line('  Seeding fixture data...');
        $fixtures->seed($user);

        $this->newLine();
        $this->info(sprintf('✅ Fixtures applied to [%s].', $email));
        $this->table(['Field', 'Value'], [
            ['Email',          $user->email],
            ['Name',           $user->name],
            ['Months of data', '12'],
            ['Wallets',        '4 (Compte courant, Livret A, Assurance vie, Argent de poche)'],
        ]);

        return 0;
    }
}
