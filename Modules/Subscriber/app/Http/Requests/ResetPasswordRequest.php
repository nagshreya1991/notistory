<?php

namespace Modules\Subscriber\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        
        return [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|integer|digits:4',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
        /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    public function messages(): array
    {
    return [
        'email.required' => __('The email field is required.'),
        'email.email' => __('The email must be a valid email address.'),
        'email.exists' => __('The email does not exist in our records.'),
        'otp.required' => __('The OTP field is required.'),
        'otp.integer' => __('The OTP must be an integer.'),
        'otp.digits' => __('The OTP must be exactly 4 digits.'),
        'password.required' => __('The password field is required.'),
        'password.string' => __('The password must be a valid string.'),
        'password.min' => __('The password must be at least 8 characters.'),
        'password.confirmed' => __('The password confirmation does not match.'),
    ];
   }

     /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422)
        );
    }
}
