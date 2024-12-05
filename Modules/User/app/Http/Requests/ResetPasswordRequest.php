<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
    
        return [
            'token' => 'required|string',  // Token from the reset email
            'password' => 'required|string|min:8|confirmed',  // Validate password and confirmation
            'password_confirmation' => 'required|string|min:8',
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
            // English messages
            'token.required' => __('The reset token is required.'),
            'token.string' => __('The reset token must be a string.'),
            'password.required' => __('The password is required.'),
            'password.string' => __('The password must be a string.'),
            'password.min' => __('The password must be at least 8 characters long.'),
            'password.confirmed' => __('The password confirmation does not match.'),
            'password_confirmation.required' => __('The password confirmation is required.'),
            'password_confirmation.string' => __('The password confirmation must be a string.'),
            'password_confirmation.min' => __('The password confirmation must be at least 8 characters long.'),
    
        ];
    }
    /**
     * Create a json response on validation errors.
     *
     * @param Validator $validator
     * @return JsonResponse
     */
    public function failedValidation(Validator $validator): JsonResponse
    {
        throw new HttpResponseException(response()->json([
            'res' => false,
            'msg' => $validator->errors()->first(),
            'data' => ""
        ]));

    }
}
