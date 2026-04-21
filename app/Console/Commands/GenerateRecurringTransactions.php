<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\RecurringTransaction;
use App\Models\ScheduledTransaction;
use App\Models\Transaction;
use App\Services\RecurringTransactionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('spendly:generate-recurring-transactions')]
#[Description('Generate transactions for active recurring rules and scheduled transactions due today')]
class GenerateRecurringTransactions extends Command
{
    public function __construct(private readonly RecurringTransactionService $recurringService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $today = today();

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
            $this->line(sprintf('Recurring: %s (%s)', $rule->description, $rule->amount));
        }

        $scheduledDue = ScheduledTransaction::query()
            ->where('is_generated', false)
            ->where('scheduled_date', '<=', $today)
            ->get();

        foreach ($scheduledDue as $scheduled) {
            Transaction::create([
                'user_id' => $scheduled->user_id,
                'wallet_id' => $scheduled->wallet_id,
                'category_id' => $scheduled->category_id,
                'type' => $scheduled->type,
                'amount' => $scheduled->amount,
                'description' => $scheduled->description,
                'date' => $scheduled->scheduled_date,
            ]);
            $scheduled->update(['is_generated' => true]);
            $this->line(sprintf('Scheduled: %s (%s)', $scheduled->description, $scheduled->amount));
        }

        $this->info(sprintf('Done. %d recurring + %d scheduled transaction(s) generated.', $due->count(), $scheduledDue->count()));
    }
}
