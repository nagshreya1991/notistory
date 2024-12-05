<?php

namespace Modules\Subscriber\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust this if you need authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }
    public function messages(): array
    {
    return [
        'email.required' => __('The email field is required.'),
        'email.email' => __('The email must be a valid email address.'),
        'email.exists' => __('The email does not exist in our records.'),
    ];
    }
}
