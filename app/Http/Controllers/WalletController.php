<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    public function index(Request $request): Response
    {
        $wallets = Wallet::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Wallets/Index', [
            'wallets' => $wallets,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Wallets/Form');
    }

    public function store(StoreWalletRequest $request, WalletService $walletService): RedirectResponse
    {
        $wallet = $walletService->create($request->user(), $request->validated());

        return redirect()->route('wallets.budget.show', $wallet)->with('success', 'Portefeuille créé avec succès.');
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

    public function update(UpdateWalletRequest $request, Wallet $wallet, WalletService $walletService): RedirectResponse
    {
        $this->authorize('update', $wallet);
        $walletService->update($wallet, $request->validated());

        return redirect()->route('wallets.index')->with('success', 'Portefeuille mis à jour.');
    }

    public function destroy(Wallet $wallet, WalletService $walletService): RedirectResponse
    {
        $this->authorize('delete', $wallet);
        $walletService->delete($wallet);

        return redirect()->route('wallets.index')->with('success', 'Portefeuille supprimé.');
    }
}
