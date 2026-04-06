<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\WalletInvitation;
use App\Services\UserService;
use App\Services\WalletInvitationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WalletInvitationService $invitationService,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = $this->userService->register($request->validated());

        event(new Registered($user));

        Auth::login($user);

        $pendingInvitation = $this->invitationService->findFirstPendingForEmail($user->email);

        return $pendingInvitation instanceof WalletInvitation
            ? redirect()->route('wallet-invitations.show', $pendingInvitation->token)
            : redirect(route('dashboard', absolute: false))->with('show_plan_modal', true);
    }
}
