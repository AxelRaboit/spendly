<?php

declare(strict_types=1);

namespace App\Services;

use App\Notifications\AppInvitationNotification;
use Illuminate\Support\Facades\Notification;

class AppInvitationService
{
    public function send(string $email, string $message, ?string $credentialEmail = null, ?string $credentialPassword = null): void
    {
        Notification::route('mail', $email)
            ->notify(new AppInvitationNotification($message, $credentialEmail, $credentialPassword));
    }
}
