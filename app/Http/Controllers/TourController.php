<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TourSeederService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TourController extends Controller
{
    public function __construct(private readonly TourSeederService $seeder) {}

    public function seed(Request $request): JsonResponse
    {
        $wallet = $this->seeder->seed($request->user());

        return response()->json(['wallet_id' => $wallet->id]);
    }

    public function cleanup(Request $request): RedirectResponse
    {
        $this->seeder->cleanup($request->user());

        return redirect()->route('wallets.index')
            ->with('success', __('flash.wallet.deleted'));
    }
}
