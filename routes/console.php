<?php

declare(strict_types=1);

use App\Console\Commands\GenerateRecurringTransactions;
use App\Console\Commands\PruneImportFiles;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(GenerateRecurringTransactions::class)->dailyAt('06:00');
Schedule::command(PruneImportFiles::class)->dailyAt('03:00');
