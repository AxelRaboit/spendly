<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PlanService;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['q', 'category_id', 'wallet_id', 'type', 'date_from', 'date_to', 'tag']);

        $result = $this->searchService->search($user, $filters);

        return Inertia::render('Search/Index', [
            'transactions' => $result['transactions'],
            'categories' => $user->categoryOptions(),
            'wallets' => $user->walletOptions(),
            'filters' => $filters,
            'isFreeLimited' => $result['isFreeLimited'],
            'freeLimitDays' => $this->planService->freeTransactionHistoryDays(),
        ]);
    }
}
