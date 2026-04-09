<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Enums\PolicyAction;
use App\Http\Requests\StoreSimpleTransactionRequest;
use App\Http\Requests\UpdateSimpleTransactionRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimpleWalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService) {}

    public function show(Request $request, Wallet $wallet): Response
    {
        $this->authorize(PolicyAction::View->value, $wallet);
        abort_if($wallet->isBudgetMode(), HttpStatus::NotFound->value);

        return Inertia::render('Wallets/Simple/Show', $this->walletService->getSimpleWalletData($wallet, $request->user()));
    }

    public function store(StoreSimpleTransactionRequest $request, Wallet $wallet, TransactionService $transactionService): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $wallet);

        $transactionService->create($request->user(), $request->validated());

        return back()->with('success', __('flash.transaction.created'));
    }

    public function update(UpdateSimpleTransactionRequest $request, Wallet $wallet, Transaction $transaction, TransactionService $transactionService): RedirectResponse
    {
        $this->authorize(PolicyAction::Update->value, $wallet);
        abort_if($transaction->wallet_id !== $wallet->id, HttpStatus::NotFound->value);

        $transactionService->update($transaction, $request->validated());

        return back()->with('success', __('flash.transaction.updated'));
    }
}
