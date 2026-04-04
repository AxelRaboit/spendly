<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filters\TransactionFilter;
use App\Http\Requests\DestroyTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request, TransactionFilter $filter): Response
    {
        $transactions = Transaction::query()
            ->where('user_id', $request->user()->id)
            ->with('category')
            ->filter($filter)
            ->latest('date')
            ->paginate(15)
            ->withQueryString();

        $categories = $request->user()->categories()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'categories' => $categories,
            'filters' => $request->only('search', 'category_id'),
        ]);
    }

    public function create(Request $request): Response
    {
        $categories = $request->user()->categories()->get();

        return Inertia::render('Transactions/Form', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreTransactionRequest $request, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->create($request->user(), $request->validated());

        return redirect(url()->previous(route('transactions.index')))->with('success', 'Transaction créée.');
    }

    public function show(Request $request, Transaction $transaction): Response
    {
        $this->authorize('view', $transaction);

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction->load('category'),
        ]);
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        $this->authorize('update', $transaction);

        $categories = $request->user()->categories()->get();

        return Inertia::render('Transactions/Form', [
            'transaction' => $transaction,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->update($transaction, $request->validated());

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    public function destroy(DestroyTransactionRequest $request, Transaction $transaction, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->delete($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
