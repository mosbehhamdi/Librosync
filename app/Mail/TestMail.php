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
        return new Content(
            view: 'emails.test',
            with: [
                'name' => 'Test User',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ]
        );
    }
}