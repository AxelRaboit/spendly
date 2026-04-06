<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletInvitation;
use App\Services\WalletInvitationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class WalletInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly WalletInvitation $invitation,
        private readonly User $inviter,
        private readonly Wallet $wallet,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(sprintf('/wallet-invitations/%s', $this->invitation->token));

        return (new MailMessage)
            ->subject(__('mail.wallet_invitation.subject', ['name' => $this->inviter->name]))
            ->greeting(__('mail.wallet_invitation.greeting'))
            ->line(__('mail.wallet_invitation.line', [
                'name' => $this->inviter->name,
                'wallet' => $this->wallet->name,
                'role' => $this->invitation->role->value,
            ]))
            ->line(__('mail.wallet_invitation.email_hint', ['email' => $this->invitation->email]))
            ->action(__('mail.wallet_invitation.action'), $url)
            ->line(__('mail.wallet_invitation.expires', ['days' => WalletInvitationService::INVITATION_EXPIRY_DAYS]));
    }
}
