<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ScheduledTransaction;
use App\Models\User;

class ScheduledTransactionService
{
    public function create(User $user, array $data): ScheduledTransaction
    {
        /** @var ScheduledTransaction $scheduled */
        $scheduled = $user->scheduledTransactions()->create($data);

        return $scheduled;
    }

    public function update(ScheduledTransaction $scheduled, array $data): void
    {
        $scheduled->update($data);
    }

    public function delete(ScheduledTransaction $scheduled): void
    {
        $scheduled->delete();
    }
}
