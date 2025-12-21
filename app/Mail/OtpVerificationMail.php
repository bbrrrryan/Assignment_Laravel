<?php
/**
 * Author: Liew Zi Li
 */
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $userName;


    public function __construct($otpCode, $userName)
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
    }


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
