<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\User\Models\User;
use Modules\Subscriber\Models\Subscriber;
use Modules\Story\Models\Story;
use Modules\Story\Models\SubscriberStory;
use App\Models\UserDeviceToken;
use App\Services\FirebaseService;
use Log;

class LaunchSubscriberStories extends Command
{
    protected $signature = 'launch:stories';
    protected $description = 'Launch subscriber stories based on scheduled time';

    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        $this->firebaseService = $firebaseService;
    }

    public function handle()
    {
        Log::info('LaunchSubscriberStories command is running...');
        Log::info(now());
        // Fetch subscriber stories scheduled to launch within the next minute
        $now = now();
        $nextFiveMinutes = $now->copy()->addMinute(5);

        $subscriberStories = SubscriberStory::where('launch_status', 0)
            ->where('scheduled_time', '>=', $now) // Scheduled to launch in the future
            ->where('scheduled_time', '<=', $nextFiveMinutes) // Scheduled to launch within the next minute
            ->get();
        Log::info($subscriberStories);
        foreach ($subscriberStories as $subscriberStory) {
            // Update launch status
            $subscriberStory->update(['launch_status' => 1, 'updated_at' => now()]);

            // Get story details
            $story = Story::find($subscriberStory->story_id);

            if ($story) {
                // Send push notification to the subscriber
                $this->sendPushNotification($subscriberStory, $story);
                Log::info("Launched Subscriber Story ID: {$subscriberStory->id} for Subscriber ID: {$subscriberStory->subscriber_id}");
            } else {
                Log::warning("Story ID {$subscriberStory->story_id} not found for Subscriber Story ID: {$subscriberStory->id}");
            }
        }

        Log::info('Checked and launched subscriber stories.');
    }

    protected function sendPushNotification($subscriberStory, $story)
    {
        // Fetch device tokens for the subscriber
        $subscriber = Subscriber::find($subscriberStory->subscriber_id);
        $deviceTokens = UserDeviceToken::where('user_id', $subscriber->user_id)->pluck('device_token')->toArray();

        if (!empty($deviceTokens)) {
            $title = "{$story->name}"; // Use story title
            $body = $story->pitch; // Use story description
            Log::info($deviceTokens);
            Log::info($title);
            Log::info($body);
            $data = [];

            $this->firebaseService->sendPushNotification($deviceTokens, $title, $body, $data);
        }
    }
}
