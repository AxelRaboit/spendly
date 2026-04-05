<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTransferRequest;
use App\Services\WalletTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WalletTransferController extends Controller
{
    public function store(StoreTransferRequest $request, WalletTransferService $service): RedirectResponse
    {
        $service->create($request->user(), $request->validated());

        return back()->with('success', 'Virement effectué.');
    }

    public function destroy(Request $request, string $transferId, WalletTransferService $service): RedirectResponse
    {
        $service->deleteByTransferId($transferId, $request->user());

        return back()->with('success', 'Virement supprimé.');
    }
}
