<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PolicyAction;
use App\Exceptions\PlanLimitException;
use App\Http\Requests\ReorderRequest;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Wallet;
use App\Services\PlanService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly PlanService $planService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Wallets/Index', [
            'wallets' => $this->walletService->getWalletsWithBalances($request->user()),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Wallets/Form');
    }

    public function store(StoreWalletRequest $request): RedirectResponse
    {
        try {
            $wallet = $this->walletService->create($request->user(), $request->validated());

            $route = $wallet->isSimpleMode()
                ? redirect()->route('wallets.simple.show', $wallet)
                : redirect()->route('wallets.budget.show', $wallet);

            return $route->with('success', __('flash.wallet.created'));
        } catch (PlanLimitException $planLimitException) {
            return back()
                ->with('plan_error', $planLimitException->limitKey->value)
                ->with('plan_error_limit', $this->planService->freeWalletLimit());
        }
    }

    public function show(Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::View->value, $wallet);

        return $wallet->isSimpleMode()
            ? redirect()->route('wallets.simple.show', $wallet)
            : redirect()->route('wallets.budget.show', $wallet);
    }

    public function edit(Wallet $wallet): Response
    {
        $this->authorize(PolicyAction::Update->value, $wallet);

        return Inertia::render('Wallets/Form', [
            'wallet' => $wallet,
        ]);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $wallet);
        $this->walletService->update($wallet, $request->validated());

        return redirect()->route('wallets.index')->with('success', __('flash.wallet.updated'));
    }

    public function toggleDashboard(Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $wallet);
        $this->walletService->toggleDashboard($wallet);

        return back();
    }

    public function reorder(ReorderRequest $request): RedirectResponse
    {
        $this->walletService->reorder($request->user(), $request->validated()['ids']);

        return back();
    }

    public function destroy(Wallet $wallet): RedirectResponse
    {
        $this->authorize(PolicyAction::Delete->value, $wallet);
        $this->walletService->delete($wallet);

        return redirect()->route('wallets.index')->with('success', __('flash.wallet.deleted'));
    }
}
