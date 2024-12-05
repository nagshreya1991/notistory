<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Story\Models\Story;
use Modules\Frontend\Services\FrontendService;
use Illuminate\Http\JsonResponse;
use Modules\Frontend\Models\Configs;
use Modules\Frontend\Models\Support;
use Modules\Frontend\Http\Requests\AddSupportRequest;

class FrontendController extends Controller
{
    protected $frontendService;

    public function __construct(FrontendService $frontendService)
    {
        $this->frontendService = $frontendService;
    }

    /**
    * Handle the request to retrieve the list of  Story.
    *
    * @param Request $request
    * @return JsonResponse
   */
    public function storyList(Request $request)
    {
        $request->validate([
            'status' => 'required|integer',
        ]);
        $story = new Story();
        $stories = $this->frontendService->getStoriesByStatus($request->status);
        return response()->json([
            'status' => true,
            'data' => $stories,
        ] , 200);
    }

    /**
     * Handle the request to create a testimonial.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createTestimonial(Request $request)
    {
       
        $request->validate([
            'image' => 'required|image|max:2048',
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $testimonial = $this->frontendService->createTestimonial($request);
       
        return response()->json([
            'status' => true,
            'data' => $testimonial,
        ], 201);
    }

     /**
     * Fetch the list of testimonials.
     *
     * @return JsonResponse
     */
    public function testimonialList(): JsonResponse
    {
        $testimonials = $this->frontendService->getTestimonialList();

        return response()->json([
            'status' => true,
            'data' => $testimonials,
        ], 200);
    }

    /**
     * Get homepage details by name.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getHomepageDetails(Request $request): JsonResponse
    {
      
        $request->validate([
            'name' => 'required|string',
        ]);

        $homepageDetails = $this->frontendService->getHomepageDetailsByName($request->name);
     
        if (!$homepageDetails) {
            return response()->json([
                'status' => false,
                'message' => 'Homepage details not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $homepageDetails,
        ], 200);
    }

    public function getPage(Request $request): JsonResponse
    {

        $request->validate([
            'name' => 'required|string',
        ]);

        $page = Configs::where('name', $request->name)->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => 'Page details not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $page,
        ], 200);
    }
   
    
    /**
     * Handle the request to create a support ticket.
     *
     * @param AddSupportRequest $request
     * @return JsonResponse
     */
    public function createSupport(AddSupportRequest $request): JsonResponse
    {
        // Create the support ticket via the service
        $support = $this->frontendService->createSupport($request->only(['title', 'content']));

        return response()->json([
            'status' => true,
            'data' => $support,
        ], 201);
    }
     /**
     * Fetch the list of support tickets.
     *
     * @return JsonResponse
     */
    public function supportList(): JsonResponse
    {
        $supports = $this->frontendService->getSupportList();

        return response()->json([
            'status' => true,
            'data' => $supports,
        ], 200);
    }

}
