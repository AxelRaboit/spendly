<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\WalletInvitation;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'plan' => $user?->plan,
                'isTrialing' => $user !== null && app(PlanService::class)->isTrialing($user),
                'trialEndsAt' => $user?->trial_ends_at instanceof Carbon ? $user->trial_ends_at->toISOString() : null,
            ],
            'locale' => $user !== null ? $user->locale : config('app.fallback_locale', 'fr'),
            'planLimits' => [
                'wallet' => PlanService::FREE_WALLET_LIMIT,
                'goal' => PlanService::FREE_GOAL_LIMIT,
                'recurring' => PlanService::FREE_RECURRING_LIMIT,
                'transactionHistoryDays' => PlanService::FREE_TRANSACTION_HISTORY_DAYS,
                'canExportImport' => $user !== null && app(PlanService::class)->canExportImport($user),
                'proPrice' => PlanService::PRO_PRICE,
            ],
            'pendingInvitations' => $user !== null
                ? Inertia::lazy(fn () => WalletInvitation::where('email', $user->email)->pending()->count())
                : 0,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
                'plan_error' => $request->session()->get('plan_error'),
                'plan_error_limit' => $request->session()->get('plan_error_limit'),
            ],
        ];
    }
}
