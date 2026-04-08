<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountDeletedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.accountDeleted.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.account-deleted',
            with: [
                'userName' => $this->userName,
                'appName' => config('app.name'),
            ],
        );
    }
}
