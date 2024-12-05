<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Log;
use Modules\Story\Models\Story;
use Modules\Story\Models\StoryPage;
use Modules\Story\Models\SubscriberStory;
use Modules\Subscriber\Models\Subscriber;
use Modules\User\Http\Controllers\UserController;
use App\Services\FirebaseService;
use App\Models\UserDeviceToken;

class SendNotistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:notistory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Notistory notification to subscribers based on the story';


    protected FirebaseService $firebaseService;
    public function __construct(FirebaseService $firebaseService)
    {
        parent::__construct();
        //$this->firebaseService = $firebaseService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $userController = app(UserController::class);
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
            $storyPage = StoryPage::find($subscriberStory->story_page_id);
            $subscriber = Subscriber::find($subscriberStory->subscriber_id);
            $deviceTokens = UserDeviceToken::where('user_id', $subscriber->user_id)->pluck('device_token')->toArray();

            if ($storyPage) {
                // Strip HTML tags to extract the plain text
                $plainTextContent = strip_tags($storyPage->content ?? 'Check out the latest page!');
                $plainTextContent = html_entity_decode($plainTextContent); // Converts &nbsp; and other entities to plain text

                // Ensure the plain text content fits notification constraints
                $notificationBody = substr($plainTextContent, 0, 240); // Firebase limit for body length is 240 characters
                $request = new \Illuminate\Http\Request([
                    'device_tokens' => $deviceTokens, // Adjust to your data model
                    'title' => $storyPage->title ?? "New page available: {$story->title}",
                    'body' => $notificationBody,
                    'data' => ['story_id' => $storyPage->story_id,'story_page_id' => $storyPage->story_page_id],
                ]);

                $response = $userController->sendNotification($request);
                Log::info($response->getContent());
            } else {
                Log::warning("Story ID {$subscriberStory->story_page_id} not found for Subscriber Story ID: {$subscriberStory->id}");
            }
        }
    }

    /**
     * Send push notification to the subscribers
     *
     * @param $subscriberStory
     * @param Story $story
     */
    protected function sendNotification($subscriberStory, $story): void
    {
        $subscriber = Subscriber::find($subscriberStory->subscriber_id);

        Log::info("Notification yet to send");
        $title = $story->title;  // Title of the story
        $body = $story->pitch_line;   // Description or pitch of the story
        $data = [
            'story_id' => $story->id,
            // Add more custom data if needed
        ];

        $this->firebaseService->sendPushNotification($deviceTokens, $title, $body, $data);
    }
}
