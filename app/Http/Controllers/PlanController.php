<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PlanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Plan/Index');
    }

    public function upgrade(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->plan = PlanType::Pro;
        $user->trial_ends_at = null;
        $user->save();

        return redirect()->route('plan.index')->with('success', 'Vous êtes maintenant sur le plan Pro.');
    }

    public function downgrade(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->plan = PlanType::Free;
        $user->trial_ends_at = null;
        $user->save();

        return redirect()->route('plan.index')->with('success', 'Vous êtes repassé sur le plan Free.');
    }
}
