<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent once, immediately after a Landlord creates a Tenant or Caretaker
 * account. Carries the auto-generated password in plain text — standard
 * for a one-time temporary credential email as long as the recipient is
 * expected to change it on first login.
 */
class NewUserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullName,
        public string $email,
        public string $username,
        public string $password,
        public string $role,        // 'tenant' or 'caretaker'
        public string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Rental System Account — Login Details',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-user-credentials',
        );
    }
}
