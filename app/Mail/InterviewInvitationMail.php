<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    private $interview;
    private $is_update;
    public function __construct($interview, $is_update = false)
    {
        $this->interview = $interview;
        $this->is_update = $is_update;
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $company = config('app.company');
        return new Envelope(
            subject: 'THƯ MỜI THAM GIA PHỎNG VẤN - ' . strtoupper($company),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.interview.invitation',
            with: [
                'interview' => $this->interview
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