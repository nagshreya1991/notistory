<?php

namespace Modules\User\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Exception;
use Modules\Author\Models\Author;
use Modules\Subscriber\Models\Subscriber;
use Modules\User\Models\User;
use Helper;
use App\Mail\PasswordResetMail;
use Carbon\Carbon;


class UserService
{
    /**
     * Register a new user and create an access token.
     *
     * @param array $requestData
     * @return array
     */
    public function register(array $requestData): array
    {
        try {
            $user = User::create([
                'name' => $requestData['name'],
                'email' => $requestData['email'],
                'role' => $requestData['role'],
                'password' => Hash::make($requestData['password']),
                'verification_token' => Str::random(60)
            ]);

            $userSkills = null;

            if ((int)$requestData['role'] === User::ROLE_AUTHOR) {
                $author = Author::create([
                    'user_id' => $user->id,
                    'phone_number' => $requestData['phone_number'],
                    'case_keywords' => $requestData['case_keywords'] ?? null,
                    'portfolio_link' => $requestData['portfolio_link'] ?? null,
                ]);

                $skills = $requestData['skills'] ?? [];
                $author->skills()->sync($skills);
                $userSkills = $author->skills;
            }

            if ((int)$requestData['role'] === User::ROLE_SUBSCRIBER) {
                Subscriber::create([
                    'user_id' => $user->id,
                    'phone_number' => $requestData['phone_number'],
                ]);
            }

            $emailData = [
                'userName' => $user->name,
                'token' => $user->verification_token,
                'siteUrl' => config('app.site_url'),
                'siteName' => config('app.site_name'),
                'appUrl' => config('app.url')
            ];

            Helper::sendMail($user->email, $user->name, $emailData, 'emails.verify-email', 'Verify Your Email for ' . config('app.site_name'));

            return [
                'status' => true,
                'message' => __('Thank you for registering! Please check your email inbox to verify your account and continue.'),
                'data' => $user,
            ];
        } catch (Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred during registration. Please try again.',
            ];
        }
    }

    /**
     * @param $token
     * @return array
     */
    public function verifyEmail($token)
    {
        $user = User::where('verification_token', $token)->first();

        if ($user) {
            $user->verification_token = null;
            $user->email_verified_at = now();
            $user->save();
            
            $message = "Your email has been successfully verified! You’re all set to unleash your imagination and showcase your writing talents on the NotiStories platform. Start creating as an author and let your stories shine!";
            if ((int)$user->role === User::ROLE_SUBSCRIBER) {
                $message = "Your email has been successfully verified! You’re all set to dive into world of stories through our app. Download the app and log in to dive into your NotiStories experience.";
            }

            return [
                'status' => true,
                'message' => $message,
                'data' => $user,
            ];
        }

        return [
            'status' => false,
            'message' => 'Invalid verification token.',
            'data' => $user,
        ];
    }

    /**
     * Create an access token for the given user.
     *
     * @param User $user
     * @return string
     */
    protected function createAccessToken(User $user): string
    {
        return $user->createToken('Notistory')->accessToken;
    }

    /**
     * Handle user login and create an access token.
     *
     * @param array $requestData
     * @return array
     */
    public function login(array $requestData): array
    {
        try {
            if (Auth::attempt(['email' => $requestData['email'], 'password' => $requestData['password']])) {
                $user = Auth::user();
                $user->token = $this->createAccessToken($user);
                if ((int)$user->role === User::ROLE_AUTHOR) {
                    $author = Author::where('user_id', $user->id)->with('skills')->first();
                    $user->skills = $author->skills->pluck('name');
                    $user->author_id = $author->id;
                }
                if ((int)$user->role === User::ROLE_SUBSCRIBER) {
                    $subscriber = Subscriber::where('user_id', $user->id)->first();
                    $user->subscriber_id = $subscriber->id;

                }
                $user->current_time = Carbon::now();
                return [
                    'status' => true,
                    'message' => 'Login successful.',
                    'data' => $user,
                ];
            }

            return [
                'status' => false,
                'message' => __('Invalid credentials.'),
                'data' => null,
            ];
        } catch (Exception $e) {
            Log::error('User login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => __('An error occurred during login. Please try again.'),
                'data' => null,
            ];
        }
    }

    /**
     * Send a password reset link to the user's email.
     *
     * @param Request $request
     * @return array
     */
    public function ___forgetPassword(Request $request): array
    {
        try {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $token = Str::random(64);
                $webUrl = config('app.web_url');
                $appName = config('app.name');
                $url = $webUrl . '/reset-password/' . $token;

                Mail::send('email.forgetPassword', [
                    'url' => $url,
                    'site_url' => $webUrl,
                    'site_name' => $appName,
                    'name' => $user->name,
                ], function ($message) use ($user) {
                    $message->to($user->email);
                    $message->from("sender@demoupdates.com");
                    $message->subject('Password Reset Request');
                });

                $user->token = $token;
                $user->save();

                return [
                    'status' => true,
                    'message' => 'Please check your email for the password reset link.',
                    'data' => null,
                ];
            }

            return [
                'status' => false,
                'message' => 'No user found with this email address.',
                'data' => null,
            ];
        } catch (Exception $e) {
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while sending the password reset link. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Reset the user's password.
     *
     * @param array $requestData
     * @return array
     */
    // public function resetPassword(array $requestData): array
    // {
    //     try {
    //         $user = User::where('token', $requestData['token'])->first();

    //         if (!$user) {
    //             return [
    //                 'status' => false,
    //                 'message' => 'Invalid or expired token.',
    //                 'data' => null,
    //             ];
    //         }

    //         $user->password = Hash::make($requestData['password']);
    //         $user->token = null; // Clear the token
    //         $user->save();

    //         return [
    //             'status' => true,
    //             'message' => 'Your password has been reset successfully.',
    //             'data' => null,
    //         ];
    //     } catch (Exception $e) {
    //         Log::error('Password reset failed', [
    //             'error' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString(),
    //         ]);

    //         return [
    //             'status' => false,
    //             'message' => 'An error occurred while resetting the password. Please try again.',
    //             'data' => null,
    //         ];
    //     }
    // }

    /**
     * Retrieve all users.
     *
     * @return array
     */
    public function getAllUsers(): array
    {
        try {
            $users = User::all();

            return [
                'status' => true,
                'message' => 'Users retrieved successfully.',
                'data' => $users,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => __('An error occurred while retrieving users. Please try again.'),
                'data' => null,
            ];
        }
    }

    /**
     * Retrieve a user by ID.
     *
     * @param int $id
     * @return array
     */
    public function getUserById(int $id): array
    {
        try {
            $user = User::findOrFail($id);

            return [
                'status' => true,
                'message' => 'User retrieved successfully.',
                'data' => $user,
            ];
        } catch (Exception $e) {
            Log::error('Failed to retrieve user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while retrieving the user. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Update a user.
     *
     * @param array $requestData
     * @param int $id
     * @return array
     */
    public function updateUser(array $requestData, int $id): array
    {
        try {
            $user = User::findOrFail($id);
            $user->update($requestData);

            return [
                'status' => true,
                'message' => 'User updated successfully.',
                'data' => $user,
            ];
        } catch (Exception $e) {
            Log::error('Failed to update user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while updating the user. Please try again.',
                'data' => null,
            ];
        }
    }

    /**
     * Delete a user.
     *
     * @param int $id
     * @return array
     */
    public function deleteUser(int $id): array
    {
        try {
            User::findOrFail($id)->delete();

            return [
                'status' => true,
                'message' => 'User deleted successfully.',
                'data' => null,
            ];
        } catch (Exception $e) {
            Log::error('Failed to delete user', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => false,
                'message' => 'An error occurred while deleting the user. Please try again.',
                'data' => null,
            ];
        }
    }
     /**
     * Handle forgot password process.
     */
    public function forgotPassword(array $data): array
    {
    try {
        // Fetch user by email
        $user = User::where('email', $data['email'])->first();

        if ($user) {
            // Generate reset token
            $token = Str::random(64);
           // $webUrl = config('app.web_url');
            $siteUrl = config('app.site_url');
            $appName = config('app.name');
            $url = $siteUrl . 'reset-password/' . $token;

            // Prepare email data
            $emailData = [
                'url' => $url,
                'site_url' => $siteUrl,
                'site_name' => $appName,
                'name' => $user->name,
            ];

            // Send the email using the helper with the user's name, email, and data
            Helper::sendMail($user->email, $user->name, $emailData, 'emails.forgotPassword', 'Verify Your Email for ' . config('app.site_name'));

            // Save the token in the user's record
            $user->remember_token = $token;
            $user->save();

            return [
                'status' => true,
                'message' => __('Please check your email for the password reset link.'),
                'data' => $token,
            ];
        }

        // If no user found
        return [
            'status' => false,
            'message' => __('No user found with this email address.'),
            'data' => null,
        ];
    } catch (Exception $e) {
        // Log any error
        Log::error('Password reset failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return [
            'status' => false,
            'message' => __('An error occurred while sending the password reset link. Please try again.'),
            'data' => null,
        ];
    }
   }
    

    /**
     * Handle reset password process.
     */
    public function resetPassword(array $data): array
    {
        try {
            // Fetch the user using the provided token
            $user = User::where('remember_token', $data['token'])->first();
    
            // Check if user exists
            if (!$user) {
                return [
                    'status' => false,
                    'message' => __('Invalid token. Please try again.'),
                    'data' => null,
                ];
            }
    
          
            $password = $data['password'];
            $passwordConfirmation = $data['password_confirmation'];
    
            if ($password !== $passwordConfirmation) {
                return [
                    'status' => false,
                    'message' => __('Passwords do not match.'),
                    'data' => null,
                ];
            }
    
            // Update the user's password
            $user->password = bcrypt($password);  // Hash the new password
            $user->remember_token = null;  // Clear the reset token
            $user->save();
    
            return [
                'status' => true,
                'message' => __('Your password has been successfully reset.'),
                'data' => null,
            ];
    
        } catch (Exception $e) {
            // Log any error
            Log::error('Password reset failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return [
                'status' => false,
                'message' => __('An error occurred while resetting the password. Please try again.'),
                'data' => null,
            ];
        }
    }
}
