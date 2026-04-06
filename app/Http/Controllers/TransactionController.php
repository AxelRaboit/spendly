<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\DestroyTransactionRequest;
use App\Http\Requests\StoreSplitTransactionRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\AttachmentService;
use App\Services\PlanService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TransactionController extends Controller
{
    public function store(StoreTransactionRequest $storeTransactionRequest, TransactionService $transactionService, AttachmentService $attachmentService, PlanService $planService): RedirectResponse
    {
        $data = $storeTransactionRequest->safe()->except('attachment');
        $transaction = $transactionService->create($storeTransactionRequest->user(), $data);

        if ($storeTransactionRequest->hasFile('attachment') && $planService->isPro($storeTransactionRequest->user())) {
            $attachmentService->store($storeTransactionRequest->file('attachment'), $transaction);
        }

        return back()->with('success', __('flash.transaction.created'));
    }

    public function update(UpdateTransactionRequest $updateTransactionRequest, Transaction $transaction, TransactionService $transactionService, AttachmentService $attachmentService, PlanService $planService): RedirectResponse
    {
        $data = $updateTransactionRequest->safe()->except(['attachment', 'remove_attachment']);
        $transactionService->update($transaction, $data);

        if ($updateTransactionRequest->boolean('remove_attachment')) {
            $attachmentService->delete($transaction);
        } elseif ($updateTransactionRequest->hasFile('attachment') && $planService->isPro($updateTransactionRequest->user())) {
            $attachmentService->delete($transaction);
            $attachmentService->store($updateTransactionRequest->file('attachment'), $transaction);
        }

        return back()->with('success', __('flash.transaction.updated'));
    }

    public function destroy(DestroyTransactionRequest $destroyTransactionRequest, Transaction $transaction, TransactionService $transactionService, AttachmentService $attachmentService): RedirectResponse
    {
        $attachmentService->delete($transaction);
        $transactionService->delete($transaction);

        return back()->with('success', __('flash.transaction.deleted'));
    }

    public function storeSplit(StoreSplitTransactionRequest $storeSplitTransactionRequest, TransactionService $transactionService, PlanService $planService): RedirectResponse
    {
        abort_if(! $planService->isPro($storeSplitTransactionRequest->user()), HttpStatus::Forbidden->value);

        $data = $storeSplitTransactionRequest->validated();
        $splits = $data['splits'];
        unset($data['splits']);

        $transactionService->createSplit($storeSplitTransactionRequest->user(), $data, $splits);

        return back()->with('success', __('flash.transaction.created'));
    }

    public function destroySplit(Request $request, string $splitId, TransactionService $transactionService): RedirectResponse
    {
        $transactionService->deleteSplit($splitId, $request->user());

        return back()->with('success', __('flash.transaction.deleted'));
    }

    public function attachment(Request $request, Transaction $transaction, AttachmentService $attachmentService): BinaryFileResponse
    {
        $this->authorize('view', $transaction);
        abort_if(! $attachmentService->exists($transaction->attachment_path), HttpStatus::NotFound->value);

        return response()->file($attachmentService->path($transaction->attachment_path));
    }
}
