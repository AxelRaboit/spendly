<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
    public function __construct(private readonly WalletService $walletService) {}

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

            return redirect()->route('wallets.budget.show', $wallet)->with('success', 'Portefeuille créé avec succès.');
        } catch (PlanLimitException $planLimitException) {
            return back()
                ->with('plan_error', $planLimitException->limitKey->value)
                ->with('plan_error_limit', PlanService::FREE_WALLET_LIMIT);
        }
    }

    public function show(Wallet $wallet): RedirectResponse
    {
        $this->authorize('view', $wallet);

        return redirect()->route('wallets.budget.show', $wallet);
    }

    public function edit(Wallet $wallet): Response
    {
        $this->authorize('update', $wallet);

        return Inertia::render('Wallets/Form', [
            'wallet' => $wallet,
        ]);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): RedirectResponse
    {
        $this->authorize('update', $wallet);
        $this->walletService->update($wallet, $request->validated());

        return redirect()->route('wallets.index')->with('success', 'Portefeuille mis à jour.');
    }

    public function toggleFavorite(Wallet $wallet): RedirectResponse
    {
        $this->authorize('update', $wallet);
        $this->walletService->toggleFavorite($wallet);

        return back();
    }

    public function reorder(ReorderRequest $request): RedirectResponse
    {
        $this->walletService->reorder($request->user(), $request->validated()['ids']);

        return back();
    }

    public function destroy(Wallet $wallet): RedirectResponse
    {
        $this->authorize('delete', $wallet);
        $this->walletService->delete($wallet);

        return redirect()->route('wallets.index')->with('success', 'Portefeuille supprimé.');
    }
}
