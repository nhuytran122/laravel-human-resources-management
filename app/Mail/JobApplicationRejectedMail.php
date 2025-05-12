<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobApplicationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private $job_application;

    public function __construct($job_application)
    {
        $this->job_application = $job_application;
    }

    public function envelope(): Envelope
    {
        $company = config('app.company');
        return new Envelope(
            subject: 'THÔNG BÁO KẾT QUẢ ĐƠN ỨNG TUYỂN - ' . strtoupper($company),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.job_application.rejected',
            with: [
                'job_application' => $this->job_application
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}