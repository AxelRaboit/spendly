<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(private readonly PlanService $planService) {}

    /**
     * @param  array{q?: string, category_id?: int, wallet_id?: int, type?: string, date_from?: string, date_to?: string, tag?: string}  $filters
     * @return array{transactions: LengthAwarePaginator, isFreeLimited: bool}
     */
    public function search(User $user, array $filters): array
    {
        $query = $user->transactions()
            ->with(['category', 'wallet'])
            ->latest('date');

        if ($filters['q'] ?? null) {
            $query->where('description', 'ilike', sprintf('%%%s%%', $filters['q']));
        }

        if ($filters['category_id'] ?? null) {
            $query->where('category_id', $filters['category_id']);
        }

        if ($filters['wallet_id'] ?? null) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        if ($filters['type'] ?? null) {
            $query->where('type', $filters['type']);
        }

        if ($filters['date_from'] ?? null) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] ?? null) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if ($filters['tag'] ?? null) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        $isFreeLimited = false;
        if (! $this->planService->isPro($user)) {
            $cutoffDate = now()->subDays(PlanService::FREE_TRANSACTION_HISTORY_DAYS)->toDateString();
            $query->whereDate('date', '>=', $cutoffDate);
            $isFreeLimited = true;
        }

        return [
            'transactions' => $query->paginate(25)->withQueryString(),
            'isFreeLimited' => $isFreeLimited,
        ];
    }
}
