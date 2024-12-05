<?php

namespace Modules\Subscriber\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangeSubscriberPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'current_password' => 'required|string|min:8',
            'new_password' => 'required|string|min:8',
        ];
    }
    public function messages(): array
    {
    return [
        'current_password.required' => __('The current password is required.'),
        'current_password.string' => __('The current password must be a valid string.'),
        'current_password.min' => __('The current password must be at least 8 characters.'),
        
        'new_password.required' => __('The new password is required.'),
        'new_password.string' => __('The new password must be a valid string.'),
        'new_password.min' => __('The new password must be at least 8 characters.'),
    ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
