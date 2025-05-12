<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobOfferMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    private $job_offer;

    public function __construct($job_offer)
    {
        $this->job_offer = $job_offer;
    }

    public function envelope(): Envelope
    {
        $company = config('app.company');
        return new Envelope(
            subject: 'THƯ MỜI LÀM VIỆC - ' . strtoupper($company),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.job_offer.invitation',
            with: [
                'job_offer' => $this->job_offer,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}