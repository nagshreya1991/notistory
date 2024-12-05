<?php namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\Story\Models\Notification;


class Helper
{
    /**
     * Send email using a view template.
     *
     * @param string $email
     * @param array $data
     * @param string $viewName
     * @param string $subject
     * @return bool
     */
    public static function sendMail($email, $name, $data, $viewName, $subject)
    {
        $siteUrl = env('SITE_URL');
        $siteName = env('SITE_NAME');
        $appUrl = env('APP_URL');

        // Add extra variables to the $data array
        $data['siteUrl'] = $siteUrl;
        $data['siteName'] = $siteName;
        $data['appUrl'] = $appUrl;
        $data['name'] = $name; // Add the user's name to the data

        try {
            // Send email using Laravel's Mail facade
            Mail::send($viewName, $data, function ($message) use ($email, $subject) {
                $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending email: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendNotification($userId, $type, $message)
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'message' => $message,
        ]);
    }
}