<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user; // بيانات المستخدم
    public $url;
    public $reason;

    public function __construct($user , $url , $reason)
    {
        $this->user = $user;
        $this->url = $url ;
        $this->reason = $reason;
    }

    public function build()
    {
        return $this->subject('تم رفض حسابك بنجاح')
            ->view('emails.account-rejected')
            ->with([
                'user' => $this->user,
                'url' => $this->url,
                'reason' => $this->reason,
            ]);
    }
}
