<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    protected $signature = 'spendly:install
                            {--fresh : Drop all tables before migrating}
                            {--seed : Run database seeders}';

    protected $description = 'Install the application for local development (migrations, seeders, application parameters).';

    public function handle(): int
    {
        $this->info('Installing Spendly...');
        $this->newLine();

        $this->runMigrations();
        $this->runApplicationParameters();

        if ($this->option('seed')) {
            $this->runSeeders();
        }

        $this->newLine();
        $this->info('✅ Installation complete.');
        $this->newLine();
        $this->table(['Email', 'Password', 'Role'], [
            ['axel.raboit@gmail.com', 'password', 'ROLE_DEV'],
            ['alice@example.com',     'password', 'ROLE_USER'],
            ['bob@example.com',       'password', 'ROLE_USER'],
            ['test@example.com',      'password', 'ROLE_USER'],
        ]);

        return self::SUCCESS;
    }

    private function runMigrations(): void
    {
        $this->line('  Running migrations...');

        $command = $this->option('fresh') ? 'migrate:fresh' : 'migrate';
        Artisan::call($command, ['--force' => true], $this->output);
    }

    private function runSeeders(): void
    {
        $this->line('  Running seeders...');
        Artisan::call('db:seed', ['--force' => true], $this->output);
    }

    private function runApplicationParameters(): void
    {
        $this->line('  Syncing application parameters...');
        Artisan::call('spendly:application-parameter', [], $this->output);
    }
}
