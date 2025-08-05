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
    public $otp;

    public function __construct(User $user, $password, $pin, $otp)
    {
        $this->user = $user;
        $this->password = $password;
        $this->pin = $pin;
        $this->otp = $otp;
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
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'pin' => $this->pin,
                'otp' => $this->otp,
            ],
        );
    }


    public function attachments(): array
    {
        return [];
    }
}
