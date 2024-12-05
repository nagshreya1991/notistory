<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otp;

    /**
     * Create a new message instance.
     */
    public function __construct($otp)  // Accept $otp as a parameter
    {
        $this->otp = $otp;  // Set the OTP as a property
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Password Reset OTP')
                    ->view('emails.password_reset_otp');
    }
}