<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $url;
    public $siteUrl;
    public $siteName;
    public $name;

    /**
     * Create a new message instance.
     *
     * @param array $emailData
     * @return void
     */
    public function __construct(array $emailData)
    {
        $this->url = $emailData['url'];
        $this->siteUrl = $emailData['site_url'];
        $this->siteName = $emailData['site_name'];
        $this->name = $emailData['name'];
    }

    // /**
    //  * Get the message envelope.
    //  *
    //  * @return Envelope
    //  */
    // public function envelope()
    // {
    //     return new Envelope(
    //         subject: 'Password Reset Request',
    //     );
    // }

    /**
     * Build the message.
     *
     * @return \Illuminate\Mail\Mailable
     */
    // public function build()
    // {
    //     return $this->view('email.forgetPassword');  // Ensure this view exists
    // }
    public function build()
    {
        return $this->subject('Your Password Reset ')
                    ->view('emails.forgetPassword');
    }
}
