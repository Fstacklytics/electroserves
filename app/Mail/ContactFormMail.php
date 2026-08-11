<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification sent to the business when a visitor submits the contact form.
 *
 * Per docs/phase-0/05-pii-classification.md the submission is transmitted by
 * email only and never persisted by the application.
 */
class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, phone: ?string, service_type: string, message: string}  $submission
     */
    public function __construct(
        public readonly array $submission,
        public readonly string $serviceLabel,
        public readonly string $submittedAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('contact.email.subject', ['name' => $this->submission['name']]),
            // From must remain our own authenticated domain or the message will
            // fail SPF/DKIM; the visitor's address goes in Reply-To instead.
            replyTo: [new Address($this->submission['email'], $this->submission['name'])],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact',
            text: 'emails.contact-text',
        );
    }
}
