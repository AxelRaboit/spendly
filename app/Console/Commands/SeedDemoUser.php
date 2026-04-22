<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PlanType;
use App\Models\User;
use App\Services\DemoFixtureService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:seed {--email= : Demo user email (default: DEMO_USER_EMAIL env or demo@spendly.app)} {--password= : Demo user password (default: DEMO_USER_PASSWORD env or demo)} {--force : Reset if user already exists without confirmation}')]
#[Description('Create (or reset) a demo user with realistic fixture data')]
class SeedDemoUser extends Command
{
    public function handle(DemoFixtureService $fixtures): int
    {
        $email = $this->option('email') ?? config('demo.email');
        $password = $this->option('password') ?? config('demo.password');

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if (! $this->option('force') && ! $this->confirm(sprintf('User [%s] already exists. Delete and re-seed?', $email))) {
                $this->info('Aborted.');

                return 0;
            }

            $existing->delete();
            $this->line('  <comment>Deleted existing demo user.</comment>');
        }

        $this->info(sprintf('Seeding demo user [%s]...', $email));

        $user = User::create([
            'name' => 'Demo',
            'email' => $email,
            'email_verified_at' => now(),
            'password' => bcrypt($password),
            'plan' => PlanType::Pro,
            'is_demo' => true,
            'locale' => 'fr',
            'currency' => 'EUR',
        ]);
        $user->assignRole('ROLE_USER');

        $fixtures->seed($user);

        $this->newLine();
        $this->info('✅ Demo user created successfully.');
        $this->table(['Field', 'Value'], [
            ['Email',          $email],
            ['Password',       $password],
            ['Plan',           'Pro'],
            ['Months of data', '12'],
            ['Wallets',        '4 (Compte courant, Livret A, Assurance vie, Argent de poche)'],
        ]);

        return 0;
    }
}
