<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Http\Requests\UpdateTransferRequest;
use App\Services\WalletTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletTransferController extends Controller
{
    public function store(StoreTransferRequest $storeTransferRequest, WalletTransferService $walletTransferService): RedirectResponse
    {
        $walletTransferService->create($storeTransferRequest->user(), $storeTransferRequest->validated());

        return back()->with('success', __('flash.transfer.created'));
    }

    public function update(UpdateTransferRequest $request, string $transferId, WalletTransferService $walletTransferService): RedirectResponse
    {
        $walletTransferService->update($transferId, $request->user(), $request->validated());

        return back()->with('success', __('flash.transfer.updated'));
    }

    public function destroy(Request $request, string $transferId, WalletTransferService $walletTransferService): RedirectResponse
    {
        $walletTransferService->deleteByTransferId($transferId, $request->user());

        return back()->with('success', __('flash.transfer.deleted'));
    }
}
