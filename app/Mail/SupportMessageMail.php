<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $userEmail;
    public $subjectText;
    public $messageText;
    public $sentAt;

    public function __construct($userEmail, $subjectText, $messageText)
    {
        $this->userEmail = $userEmail;
        $this->subjectText = $subjectText;
        $this->messageText = $messageText;
    }

    public function build()
    {
        return $this->subject('Support Message')
            ->replyTo($this->userEmail)
            ->view('emails.support_message')
            ->with([
                'userEmail'   => $this->userEmail,
                'subjectText' => $this->subjectText,
                'messageText' => strip_tags($this->messageText),
                'sentAt'      => now()->format('Y-m-d — H:i'),
            ]);
    }
}
