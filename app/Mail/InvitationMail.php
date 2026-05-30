<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(public Invitation $invitation)
    {
        $this->acceptUrl = url('/invitation/' . $invitation->token);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invitation à rejoindre SynoManager');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invitation');
    }
}
