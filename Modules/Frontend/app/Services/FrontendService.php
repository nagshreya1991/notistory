<?php

namespace Modules\Frontend\Services;
use Modules\Frontend\Services\FrontendService;
use Modules\Story\Models\Story;
use Modules\Frontend\Models\Testimonial;
use Illuminate\Http\Request;
use Modules\Frontend\Models\Configs;
use Modules\Frontend\Models\Support;

class FrontendService
{
    // protected $frontend;

    // public function __construct(Frontend $frontend)
    // {
    //     $this->frontend = $frontend;
    // }
    /**
     * Retrieve the list of a story by status.
     *
     * @param int $status
     */
    public function getStoriesByStatus(int $status)
    {
        // Fetch stories with their related author and the author's user
        $stories = Story::where('status', $status)
                        ->with('author.user')  // Eager load the author and its user
                        ->get();
    
        // Set default image URLs for logo and cover
        $defaultImageUrl = config('app.url') . 'storage/app/images/no-image.jpg';
    
        // Map through each story and set the logo, cover URLs, and user name
        return $stories->map(function ($story) use ($defaultImageUrl) {
            $story->logo = !empty($story->logo) 
            ? config('app.url') . 'storage/app/' . $story->logo 
            : $defaultImageUrl;
    
            $story->cover = !empty($story->cover) 
            ? config('app.url') . 'storage/app/' . $story->cover 
            : $defaultImageUrl;
    
            // Get the user's name from the related author and only include user_name
            $user = $story->author ? $story->author->user : null;
            $story->user_name = $user ? $user->name : 'Unknown';
    
            // Remove the author object
            unset($story->author);
    
            return $story;
        });
    }
     /**
     * Create a new testimonial.
     *
     * @param Request $request
     * @return Testimonial
     */
    public function createTestimonial(Request $request)
    {
       
        // Handle image upload
        $imagePath = $request->file('image')->store('uploads/testimonials');

        // Create a new testimonial
        return Testimonial::create([
            'image' => $imagePath,
            'name' => $request->name,
            'content' => $request->content,
        ]);
    }
      /**
     * Retrieve all testimonials.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTestimonialList()
    {
            // Retrieve all testimonials
        $testimonials = Testimonial::all();

        // Iterate over each testimonial and set the full URL for the image
        foreach ($testimonials as $testimonial) {
            $testimonial->image = !empty($testimonial->image) 
                ? config('app.url') . 'storage/app/' . $testimonial->image 
                : config('app.url') . 'storage/app/images/no-image.jpg'; // Fallback to a default image
        }

        return $testimonials;
    }

      /**
     * Get homepage details by name.
     *
     * @param string $name
     * @return Configs|null
     */
    public function getHomepageDetailsByName(string $name)
    {
        return Configs::where('name', $name)->first();
    }
     /**
     * Create a new support ticket.
     *
     * @param array $data
     * @return Support
     */
    public function createSupport(array $data)
    {
        return Support::create($data);
    }
    /**
     * Retrieve all support entries.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSupportList()
    {
        return Support::all();
    }
}
