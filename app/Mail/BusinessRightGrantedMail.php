<?php

namespace App\Mail;

use App\Models\Estimate;
use App\Models\Store;
use App\Models\BusinessRight;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BusinessRightGrantedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Store $store,
        public Estimate $estimate,
        public BusinessRight $businessRight,
        public array $allWinners = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $rankingText = match ($this->businessRight->ranking) {
            1 => '第1位',
            2 => '第2位',
            3 => '第3位',
            default => '第' . $this->businessRight->ranking . '位'
        };

        return new Envelope(
            subject: "【営業権獲得・{$rankingText}】見積もり案件の営業権を獲得しました - " . $this->estimate->name . '様',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.business-right-granted',
            with: [
                'store' => $this->store,
                'estimate' => $this->estimate,
                'businessRight' => $this->businessRight,
                'allWinners' => $this->allWinners,
                'ranking' => $this->businessRight->ranking,
                'bidAmountMin' => number_format($this->businessRight->bid_amount_min),
                'bidAmountMax' => number_format($this->businessRight->bid_amount_max),
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
