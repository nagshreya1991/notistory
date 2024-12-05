<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $firebase = (new Factory)->withServiceAccount(config('firebase.credentials_path'));
        $this->messaging = $firebase->createMessaging();
    }

    /**
     * Send a push notification to specific devices
     *
     * @param array $deviceTokens Array of device tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Optional data payload
     * @return bool
     */
    public function sendPushNotification(array $deviceTokens, string $title, string $body, array $data = [], ?string $imageUrl = null)
    {
        $notification = Notification::create($title, $body, $imageUrl);

        // Message with notification and additional data payload
        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($data);

        try {
            // Send the notification to all device tokens
            $this->messaging->sendMulticast($message, $deviceTokens);
            Log::info('Push notification sent to devices: ' . implode(', ', $deviceTokens));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send push notification: ' . $e->getMessage());
            return false;
        }
    }
}
