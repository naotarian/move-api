<?php

namespace App\Mail;

use App\Models\Estimate;
use App\Models\BusinessRight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Estimate $estimate,
        public array $businessRights = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '【引越し見積もり】営業権獲得業者のご案内 - ' . $this->estimate->name . '様',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-notification',
            with: [
                'estimate' => $this->estimate,
                'businessRights' => $this->businessRights,
                'winnerCount' => count($this->businessRights),
                'topBidAmount' => !empty($this->businessRights) ? number_format($this->businessRights[0]['bid_amount_min']) : '0',
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
