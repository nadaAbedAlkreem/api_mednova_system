<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user; // بيانات المستخدم
    public $url;

    public function __construct($user , $url)
    {
        $this->user = $user;
        $this->url = $url ;
    }

    public function build()
    {
        return $this->subject('تم اعتماد حسابك بنجاح')
            ->view('emails.account-approved')
            ->with([
                'user' => $this->user,
                'url' => $this->url,
            ]);
    }
}
