<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Enums\PolicyAction;
use App\Enums\WalletRole;
use App\Http\Requests\StoreWalletInvitationRequest;
use App\Models\Wallet;
use App\Models\WalletInvitation;
use App\Services\PlanService;
use App\Services\WalletInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class WalletInvitationController extends Controller
{
    public function __construct(private readonly WalletInvitationService $invitationService) {}

    public function store(StoreWalletInvitationRequest $storeWalletInvitationRequest, Wallet $wallet, PlanService $planService): JsonResponse
    {
        abort_if(! $planService->isPro($storeWalletInvitationRequest->user()), HttpStatus::Forbidden->value);
        $this->authorize(PolicyAction::ManageMembers->value, $wallet);

        $data = $storeWalletInvitationRequest->validated();

        try {
            $invitation = $this->invitationService->invite(
                $wallet,
                $storeWalletInvitationRequest->user(),
                $data['email'],
                WalletRole::from($data['role']),
            );

            return response()->json($invitation->only('id', 'email', 'role', 'expires_at'), HttpStatus::Created->value);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return response()->json(['message' => $invalidArgumentException->getMessage()], HttpStatus::UnprocessableEntity->value);
        }
    }

    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = $this->invitationService->findByToken($token);

        if (! $invitation->isPending()) {
            return redirect('/')->with('error', __('flash.invitation.expired'));
        }

        return Inertia::render('WalletInvitations/Respond', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'wallet_name' => $invitation->wallet->name,
                'inviter_name' => $invitation->inviter->name,
                'role' => $invitation->role->value,
                'expires_at' => $invitation->expires_at->toISOString(),
            ],
            'isAuthenticated' => $request->user() !== null,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        try {
            $member = $this->invitationService->acceptForUser($token, $request->user());
        } catch (InvalidArgumentException) {
            return back()->with('error', __('flash.invitation.wrong_email'));
        }

        return redirect()->route('wallets.budget.show', $member->wallet_id)
            ->with('success', __('flash.invitation.accepted'));
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->invitationService->findPendingByToken($token);

        if ($request->user()->email !== $invitation->email) {
            abort(HttpStatus::Forbidden->value);
        }

        $this->invitationService->decline($invitation);

        return redirect('/')->with('success', __('flash.invitation.declined'));
    }

    public function resend(Request $request, Wallet $wallet, WalletInvitation $invitation): JsonResponse
    {
        $this->authorize(PolicyAction::ManageMembers->value, $wallet);

        $newInvitation = $this->invitationService->resend($invitation, $request->user(), $wallet);

        return response()->json($newInvitation->only('id', 'email', 'role', 'expires_at'));
    }

    public function destroy(Request $request, Wallet $wallet, WalletInvitation $invitation): JsonResponse
    {
        $this->authorize(PolicyAction::ManageMembers->value, $wallet);

        $this->invitationService->revoke($invitation);

        return response()->json(null, HttpStatus::NoContent->value);
    }
}
