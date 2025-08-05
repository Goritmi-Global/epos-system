<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VerifyAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $pin;

    public function __construct(User $user, $password, $pin)
    {
        $this->user = $user;
        $this->password = $password;
        $this->pin = $pin;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verify-account',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
