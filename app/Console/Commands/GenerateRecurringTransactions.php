<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Services\RecurringTransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('app:generate-recurring-transactions')]
#[Description('Generate transactions for active recurring rules due today')]
class GenerateRecurringTransactions extends Command
{
    public function __construct(private readonly RecurringTransactionService $recurringService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $today = Carbon::today();

        $due = RecurringTransaction::query()
            ->where('active', true)
            ->where('day_of_month', $today->day)
            ->where(function ($q) use ($today) {
                $q->whereNull('last_generated_at')
                    ->orWhereRaw("TO_CHAR(last_generated_at, 'YYYY-MM') < ?", [$today->format('Y-m')]);
            })
            ->get();

        foreach ($due as $rule) {
            $this->recurringService->generateIfDue($rule);
            $this->line(sprintf('Generated: %s (%s)', $rule->description, $rule->amount));
        }

        $this->info(sprintf('Done. %d transaction(s) generated.', $due->count()));
    }
}
