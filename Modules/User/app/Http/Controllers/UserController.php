<?php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\RegisterRequest;
use Modules\User\Http\Requests\ForgotPasswordRequest;
use Modules\User\Http\Requests\ResetPasswordRequest;
use Modules\User\Http\Requests\LoginRequest;
use Modules\User\Services\UserService;
use App\Services\FirebaseService;
use App\Models\UserDeviceToken;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Helpers\Helper;


class UserController extends Controller
{
    protected UserService $userService;
    protected FirebaseService $firebaseService;

    /**
     * UserController constructor.
     *
     * @param UserService $userService
     * @param FirebaseService $firebaseService
     */
    public function __construct(UserService $userService, FirebaseService $firebaseService)
    {
        $this->userService = $userService;
        $this->firebaseService = $firebaseService;
    }

    /**
     * Send a notification using FirebaseService.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $deviceTokens = $request->input('device_tokens');
        $title = $request->input('title');
        $body = $request->input('body');
        $data = $request->input('data', []);
        //$imageUrl = 'https://updates.properwebtechnologies.co.in/notistories/backend/storage/app/images/no-image.jpg';

        if ($this->firebaseService->sendPushNotification($deviceTokens, $title, $body, $data)) {
            return response()->json([
                'status' => true,
                'message' => 'Notification sent successfully',
                'data' => '',
            ],201);
        }

        return response()->json([
            'status' => true,
            'message' => 'Failed to send notification',
            'data' => '',
        ],500);
    }

    public function storeDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string',
            'device_type' => 'required|in:android,ios',
        ]);

        $userId = auth()->id(); // Or $request->user_id if you’re not using auth
        $deviceToken = $request->input('device_token');
        $deviceType = $request->input('device_type');

        UserDeviceToken::updateOrCreateToken($userId, $deviceToken, $deviceType);

        return response()->json([
                'status' => true,
                'message' => 'Device token updated successfully',
                'data' => '',
            ],201);
    }

    /**
     * Handle user registration.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $response = $this->userService->register($request->validated());

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred during registration.',
            ], 500);
        }
    }

    /**
     * Handle verify email.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $response = $this->userService->verifyEmail($request->token);

            return response()->json($response, 201);
        } catch (Exception $e) {
            Log::error('User email verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred during registration.',
            ], 500);
        }
    }

    /**
     * Handle user login.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $response = $this->userService->login($request->validated());

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('User login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred during login.',
            ], 500);
        }
    }


    /**
     * Display a listing of users.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            if (auth()->user()->cannot('viewAny', User::class)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access.',
                ], 403);
            }

            $response = $this->userService->getAllUsers();

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving users.',
            ], 500);
        }
    }

    /**
     * Show the specified user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $response = $this->userService->getUserById($id);

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Failed to retrieve user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while retrieving the user.',
            ], 500);
        }
    }

    /**
     * Update the specified user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $response = $this->userService->updateUser($request->all(), $id);

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Failed to update user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while updating the user.',
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $response = $this->userService->deleteUser($id);

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Failed to delete user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred while deleting the user.',
            ], 500);
        }
    }
   
   
    /**
     * Handle forgot password request.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $response = $this->userService->forgotPassword($request->validated());
    
        return response()->json([
            'status' => $response['status'],
            'message' => $response['message'],
            'data' => $response['data'],
        ]);
    }

    /**
     * Handle reset password request.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Call the resetPassword method from UserService
        $response = $this->userService->resetPassword($request->validated());
    
        // Return the response as JSON
        return response()->json($response);
    }
}
