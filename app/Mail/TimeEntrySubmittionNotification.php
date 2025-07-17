<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TimeEntrySubmittionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;
    public string $period;

    public string $type;
    public string $nameCurrent;
    public string $nameTarg;
    /**
     * Create a new message instance.
     */
    public function __construct(string $acceptUrl,string $period,string $nameCurrent,string $nameTarg,string $type)
    { 
        $this->acceptUrl = $acceptUrl;
        $this->period = $period;
        $this->nameTarg = $nameTarg;
        $this->nameCurrent = $nameCurrent;
        $this->type = $type;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Time Entry Submittion Notification',
        );
    }

    public function build()
    {
        return $this->markdown('emails.time-entry-reminder')
            ->subject('Reminder: Submit Your Timesheet');
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'view.name',
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
