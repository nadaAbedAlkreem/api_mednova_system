<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BankAccountOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Customer $user,
        public string $otp,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('رمز التحقق من حسابك البنكي — Mednova')
            ->view('emails.bank-account-otp')
            ->with([
                'user' => $this->user,
                'otp'  => $this->otp,
            ]);
    }
}
