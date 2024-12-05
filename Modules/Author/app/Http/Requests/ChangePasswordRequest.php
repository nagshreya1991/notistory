<?php

namespace Modules\Author\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash; 

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string|min:8',
            'new_password' => [
                'required',
                'string',
                'min:8',
                function ($attribute, $value, $fail) {
                    if (Hash::check($value, $this->user()->password)) {
                        $fail('The new password must be different from the current password.');
                    }
                },
            ],
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
        'new_password.different' => __('The new password must be different from the current password.'),
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
