<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = auth()->user()->transactions()->with('category')->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    public function create()
    {
        $categories = auth()->user()->categories()->get();

        return Inertia::render('Transactions/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreTransactionRequest $request, TransactionService $transactionService)
    {
        $transactionService->create(auth()->user(), $request->validated());

        return redirect()->route('transactions.index')->with('success', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction)
    {
        $this->authorize('view', $transaction);

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction->load('category'),
        ]);
    }

    public function edit(Transaction $transaction)
    {
        $this->authorize('update', $transaction);

        $categories = auth()->user()->categories()->get();

        return Inertia::render('Transactions/Edit', [
            'transaction' => $transaction,
            'categories'  => $categories,
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, TransactionService $transactionService)
    {
        $transactionService->update($transaction, $request->validated());

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(DestroyTransactionRequest $request, Transaction $transaction, TransactionService $transactionService)
    {
        $transactionService->delete($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
