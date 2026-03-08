<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationEmailMail extends Mailable  implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $userId; // تخزين ID فقط
    public $userName; // تخزين الاسم
    public $userEmail; // تخزين الإيميل
    public $verificationUrl;

    public function __construct(Customer $user, $verificationUrl)
    {
        // خذي فقط البيانات التي تحتاجينها
        $this->userId = $user->id;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->verificationUrl = $verificationUrl;
    }

    public function build()
    {
        // الآن استخدمي البيانات المخزنة
        return $this->subject('Verify Your Email Address')
            ->view('emails.email-verification')
            ->with([
                'userName' => $this->userName,
                'userEmail' => $this->userEmail,
                'verificationUrl' => $this->verificationUrl,
            ]);
    }
}
