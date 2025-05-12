<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $interview;
    private $isUpdate;
    public function __construct($interview, $isUpdate = false)
    {
        $this->isUpdate = $isUpdate;
        $this->interview = $interview;
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
                'interview' => $this->interview,
                'isUpdate' => $this->isUpdate
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