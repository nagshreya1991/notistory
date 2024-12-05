<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;


class Helper{

    /**
     * Sends an email using Laravel's built-in Mail class.
     *
     * @param string $email The recipient's email address.
     * @param string $name The recipient's name.
     * @param array $data The necessary data.
     * @param string $template The template view name.
     * @param string $subject The subject of the email.
     *
     * @return bool True if the email is sent successfully, false otherwise.
     */
    public static function sendMail(string $email, string $name, array $data, string $template, string $subject): bool
    {
        try {
            // Resolve the view and render it with the data
            $view = resolve('view');
            $htmlContent = $view->make($template, $data)->render();

            // Send email
            Mail::html($htmlContent, function ($message) use ($email, $name, $subject) {
                $message->to($email, $name)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name')); // Use sender's email and name from config
            });

            Log::info("Email sent successfully to {$email}");
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends an email using Laravel's built-in Mail class.
     *
     * @param string $email The recipient's email address.
     * @param string $name The recipient's name.
     * @param array $data The necessary data.
     * @param string $template The template.
     * @param string $subject The subject of the email.
     * @param string $content The HTML content of the email.
     *
     * @return bool True if the email is sent successfully, false otherwise.
     */
    public static function sendMailPHPMailer(string $email, string $name, array $data, string $template, string $subject): bool
    {
        $mail = new PHPMailer(true);

        try {

            //Server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = config('mail.mailers.smtp.host');
            $mail->SMTPAuth = true;
            $mail->Username = config('mail.mailers.smtp.username');
            $mail->Password = config('mail.mailers.smtp.password');
            $mail->SMTPSecure = config('mail.mailers.smtp.encryption');
            $mail->Port = config('mail.mailers.smtp.port');

            //Recipients
            $mail->addAddress($email, $name);
            $mail->setFrom(config('mail.from.address'), config('mail.from.name'));

            //Content
            $mail->isHTML(true);
            $mail->Subject = $subject;

            // Resolve the view instance and render its contents as a string
            $view = resolve('view');
            $mail->Body = $view->make($template, $data)->render();
            if(!$mail->send()) {
                return false;
            }
            return true;
        } catch (Exception $e) {

            Log::error('Error sending email: ' . $e->getMessage());

            return false;
        }


    }
}