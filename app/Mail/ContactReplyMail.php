<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $originalMessage;
    public string $replyContent;

    /**
     * Create a new message instance.
     */
    public function __construct(string $customerName, string $originalMessage, string $replyContent)
    {
        $this->customerName = $customerName;
        $this->originalMessage = $originalMessage;
        $this->replyContent = $replyContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Phản hồi liên hệ từ SaigonShoes',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_reply',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
