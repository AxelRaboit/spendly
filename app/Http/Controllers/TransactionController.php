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
    public function store(StoreTransactionRequest $request, TransactionService $transactionService, AttachmentService $attachmentService, PlanService $planService): RedirectResponse
    {
        $data = $request->safe()->except('attachment');
        $transaction = $transactionService->create($request->user(), $data);

        if ($request->hasFile('attachment') && $planService->isPro($request->user())) {
            $attachmentService->store($request->file('attachment'), $transaction);
        }

        return back()->with('success', __('flash.transaction.created'));
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, TransactionService $transactionService, AttachmentService $attachmentService, PlanService $planService): RedirectResponse
    {
        $data = $request->safe()->except(['attachment', 'remove_attachment']);
        $transactionService->update($transaction, $data);

        if ($request->boolean('remove_attachment')) {
            $attachmentService->delete($transaction);
        } elseif ($request->hasFile('attachment') && $planService->isPro($request->user())) {
            $attachmentService->delete($transaction);
            $attachmentService->store($request->file('attachment'), $transaction);
        }

        return back()->with('success', __('flash.transaction.updated'));
    }

    public function destroy(DestroyTransactionRequest $request, Transaction $transaction, TransactionService $transactionService, AttachmentService $attachmentService): RedirectResponse
    {
        $attachmentService->delete($transaction);
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

    public function attachment(Request $request, Transaction $transaction, AttachmentService $attachmentService): BinaryFileResponse
    {
        abort_if($transaction->user_id !== $request->user()->id, HttpStatus::Forbidden->value);
        abort_if(! $attachmentService->exists($transaction->attachment_path), HttpStatus::NotFound->value);

        return response()->file($attachmentService->path($transaction->attachment_path));
    }
}
