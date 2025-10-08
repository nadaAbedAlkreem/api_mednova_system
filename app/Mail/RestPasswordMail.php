<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RestPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $token; // Token variable

    /**
     * Create a new message instance.
     */
    public function __construct($token)
    {
        $this->token = $token; // Assign token
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Reset Password Mail')
                    ->view('emails.password-reset')
                    ->with(['token' => $this->token]); // Pass the token to the view
    }
}
