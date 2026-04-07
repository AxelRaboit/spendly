<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\SendAppInvitationRequest;
use App\Models\User;
use App\Services\AdminStatsService;
use App\Services\AppInvitationService;
use App\Services\ImpersonationService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DevDashboardController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly AdminStatsService $adminStatsService,
        private readonly ImpersonationService $impersonationService,
        private readonly AppInvitationService $invitationService,
    ) {}

    public function stats(): Response
    {
        return Inertia::render('Dev/Dashboard', [
            'tab' => 'stats',
            'stats' => $this->adminStatsService->getStats(),
        ]);
    }

    public function impersonate(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot impersonate yourself.');
        abort_if($this->impersonationService->isImpersonating(), 403, 'Already impersonating a user.');

        $this->impersonationService->impersonate($user, auth()->user());

        return redirect()->route('dashboard');
    }

    public function leaveImpersonation(): RedirectResponse
    {
        $this->impersonationService->leave();

        return redirect()->route('dev.dashboard.users');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot delete your own account.');

        $this->userService->deleteUser($user);

        return back();
    }

    public function toggleRole(User $user): RedirectResponse
    {
        $this->userService->toggleRole($user);

        return back();
    }

    public function users(Request $request): Response
    {
        return Inertia::render('Dev/Dashboard', [
            'tab' => 'users',
            'users' => $this->userService->searchForAdmin(['search' => $request->query('search', '')]),
            'search' => $request->query('search', ''),
        ]);
    }

    public function invitations(): Response
    {
        return Inertia::render('Dev/Dashboard', [
            'tab' => 'invitations',
        ]);
    }

    public function sendInvitation(SendAppInvitationRequest $request): RedirectResponse
    {
        $this->invitationService->send(
            $request->input('email'),
            $request->input('message'),
            $request->input('credential_email'),
            $request->input('credential_password'),
        );

        return back()->with('success', __('admin.invitations.sent', ['email' => $request->input('email')]));
    }
}
