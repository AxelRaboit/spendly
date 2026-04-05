<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DestroyTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $request, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->create($request->user(), $request->validated());

        return back()->with('success', __('flash.transaction.created'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->update($transaction, $request->validated());

        return back()->with('success', __('flash.transaction.updated'));
    }

    public function destroy(DestroyTransactionRequest $request, Transaction $transaction, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->delete($transaction);

        return back()->with('success', __('flash.transaction.deleted'));
    }
}
