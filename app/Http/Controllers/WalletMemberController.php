<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\HttpStatus;
use App\Enums\WalletRole;
use App\Http\Requests\UpdateWalletMemberRequest;
use App\Models\Wallet;
use App\Models\WalletMember;
use App\Services\WalletInvitationService;
use App\Services\WalletMemberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class WalletMemberController extends Controller
{
    public function __construct(
        private readonly WalletMemberService $memberService,
        private readonly WalletInvitationService $invitationService,
    ) {}

    public function index(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorize('view', $wallet);

        return response()->json([
            'members' => $this->memberService->listMembers($wallet),
            'invitations' => $this->invitationService->listPendingForWallet($wallet),
        ]);
    }

    public function update(UpdateWalletMemberRequest $updateWalletMemberRequest, Wallet $wallet, WalletMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $wallet);

        try {
            $this->memberService->updateRole($member, WalletRole::from($updateWalletMemberRequest->validated()['role']));
        } catch (InvalidArgumentException $invalidArgumentException) {
            return response()->json(['message' => $invalidArgumentException->getMessage()], HttpStatus::UnprocessableEntity->value);
        }

        return response()->json(['success' => true]);
    }

    public function transferOwnership(Request $request, Wallet $wallet, WalletMember $member): JsonResponse
    {
        $this->authorize('manageMembers', $wallet);

        try {
            $currentOwner = $this->memberService->findMember($wallet, $request->user());
            $this->memberService->transferOwnership($wallet, $currentOwner, $member);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return response()->json(['message' => $invalidArgumentException->getMessage()], HttpStatus::UnprocessableEntity->value);
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, Wallet $wallet, WalletMember $member): JsonResponse
    {
        if ($member->user_id === $request->user()->id) {
            try {
                $this->memberService->removeMember($member);
            } catch (InvalidArgumentException $exception) {
                return response()->json(['message' => $exception->getMessage()], HttpStatus::UnprocessableEntity->value);
            }

            return response()->json(null, HttpStatus::NoContent->value);
        }

        $this->authorize('manageMembers', $wallet);

        try {
            $this->memberService->removeMember($member);
        } catch (InvalidArgumentException $invalidArgumentException) {
            return response()->json(['message' => $invalidArgumentException->getMessage()], HttpStatus::UnprocessableEntity->value);
        }

        return response()->json(null, HttpStatus::NoContent->value);
    }
}
