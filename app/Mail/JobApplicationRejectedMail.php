<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplicationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    private $job_application;

    public function __construct($job_application)
    {
        $this->job_application = $job_application;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $company = config('app.company');
        return new Envelope(
            subject: 'THÔNG BÁO KẾT QUẢ ĐƠN ỨNG TUYỂN - ' . strtoupper($company),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.job_application.rejected',
            with: [
                'job_application' => $this->job_application
            ],
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