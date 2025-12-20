<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $resetUrl;

    public function __construct(string $token, string $resetUrl)
    {
        $this->token = $token;
        $this->resetUrl = $resetUrl;
    }

    public function envelope()
    {
        return new Envelope(
            subject: 'Reset Password Notification',
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.reset',
            with: [
                'token' => $this->token,
                'resetUrl' => $this->resetUrl,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
