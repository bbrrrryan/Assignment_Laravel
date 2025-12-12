<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $userName;

    /**
     * Create a new message instance.
     */
    public function __construct($otpCode, $userName)
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Verify Your Account - TARUMT FMS')
                    ->view('emails.otp-verification')
                    ->with([
                        'otpCode' => $this->otpCode,
                        'userName' => $this->userName,
                    ]);
    }
}
