<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Http\Requests\ScheduledTransactionRequest;
use App\Models\ScheduledTransaction;
use App\Services\ScheduledTransactionService;
use Illuminate\Http\RedirectResponse;

class ScheduledTransactionController extends Controller
{
    public function __construct(private readonly ScheduledTransactionService $scheduledService) {}

    public function store(ScheduledTransactionRequest $scheduledTransactionRequest): RedirectResponse
    {
        $this->scheduledService->create($scheduledTransactionRequest->user(), $scheduledTransactionRequest->validated());

        return back()->with('success', __('flash.scheduled.created'));
    }

    public function update(ScheduledTransactionRequest $scheduledTransactionRequest, ScheduledTransaction $scheduledTransaction): RedirectResponse
    {
        $this->authorize('update', $scheduledTransaction);
        abort_if($scheduledTransaction->is_generated, HttpStatus::Forbidden->value);

        $this->scheduledService->update($scheduledTransaction, $scheduledTransactionRequest->validated());

        return back()->with('success', __('flash.scheduled.updated'));
    }

    public function destroy(ScheduledTransaction $scheduledTransaction): RedirectResponse
    {
        $this->authorize('delete', $scheduledTransaction);

        $this->scheduledService->delete($scheduledTransaction);

        return back()->with('success', __('flash.scheduled.deleted'));
    }
}
