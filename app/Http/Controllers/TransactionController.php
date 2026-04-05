<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\DestroyTransactionRequest;
use App\Http\Requests\StoreSplitTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\PlanService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function storeSplit(StoreSplitTransactionRequest $request, TransactionService $transactionService, PlanService $planService): RedirectResponse
    {
        abort_if(! $planService->isPro($request->user()), HttpStatus::Forbidden->value);

        $data = $request->validated();
        $splits = $data['splits'];
        unset($data['splits']);

        $transactionService->createSplit($request->user(), $data, $splits);

        return back()->with('success', __('flash.transaction.created'));
    }

    public function destroySplit(Request $request, string $splitId, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->deleteSplit($splitId, $request->user());

        return back()->with('success', __('flash.transaction.deleted'));
    }
}
