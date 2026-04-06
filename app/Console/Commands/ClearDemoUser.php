<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:clear {--email= : Demo user email (default: DEMO_USER_EMAIL env or demo@spendly.app)} {--force : Skip confirmation}')]
#[Description('Delete the demo user and all their data')]
class ClearDemoUser extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?? config('demo.email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->warn(sprintf('No user found with email [%s].', $email));

            return 0;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Delete demo user [%s] and all their data?', $email))) {
            $this->info('Aborted.');

            return 0;
        }

        $user->delete();

        $this->info(sprintf('✅ Demo user [%s] and all their data have been deleted.', $email));

        return 0;
    }
}
