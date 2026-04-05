<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ScheduledTransactionRequest;
use App\Models\ScheduledTransaction;
use Illuminate\Http\RedirectResponse;

class ScheduledTransactionController extends Controller
{
    public function store(ScheduledTransactionRequest $request): RedirectResponse
    {
        $request->user()->scheduledTransactions()->create($request->validated());

        return back()->with('success', __('flash.scheduled.created'));
    }

    public function update(ScheduledTransactionRequest $request, ScheduledTransaction $scheduledTransaction): RedirectResponse
    {
        $this->authorize('update', $scheduledTransaction);
        abort_if($scheduledTransaction->is_generated, 403);

        $scheduledTransaction->update($request->validated());

        return back()->with('success', __('flash.scheduled.updated'));
    }

    public function destroy(ScheduledTransaction $scheduledTransaction): RedirectResponse
    {
        $this->authorize('delete', $scheduledTransaction);

        $scheduledTransaction->delete();

        return back()->with('success', __('flash.scheduled.deleted'));
    }
}
