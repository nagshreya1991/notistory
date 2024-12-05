<?php

namespace Modules\Subscriber\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|integer|digits:4',
        ];
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
    ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
