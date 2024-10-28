<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Mail from LibroSync'
        );
    }

    public function content(): Content
    {
        // Let's try with a simple string content first
        return new Content(
            htmlString: '<h1>Test Email</h1><p>This is a test email from LibroSync.</p>'
        );
    }
}
