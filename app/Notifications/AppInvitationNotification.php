<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class AppInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $customMessage,
        private readonly ?string $credentialEmail = null,
        private readonly ?string $credentialPassword = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('mail.app_invitation.subject'))
            ->greeting(__('mail.app_invitation.greeting'))
            ->line($this->customMessage);

        if ($this->credentialEmail !== null || $this->credentialPassword !== null) {
            $mail->line(__('mail.app_invitation.credentials_intro'));

            if ($this->credentialEmail !== null) {
                $mail->line(__('mail.app_invitation.credential_email', ['email' => $this->credentialEmail]));
            }

            if ($this->credentialPassword !== null) {
                $mail->line(__('mail.app_invitation.credential_password', ['password' => $this->credentialPassword]));
            }
        }

        return $mail
            ->action(__('mail.app_invitation.action'), route('login'))
            ->line(__('mail.app_invitation.footer'));
    }
}
