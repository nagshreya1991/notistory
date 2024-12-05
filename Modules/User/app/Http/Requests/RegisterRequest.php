<?php

namespace Modules\User\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|integer|in:2,3',
        ];

        // Apply additional validation rules if the role is author (e.g., role = 2)
        if ((int) $this->input('role') === 2) {
            $rules = array_merge($rules, [
                'phone_number' => 'required|string|max:15',
                'case_keywords' => 'nullable|string|max:255',
                'portfolio_link' => 'nullable|url|max:255',
                'skills' => 'required|array|min:1',
            ]);
        }
         // Apply additional validation rules if the role is subscriber (e.g., role = 3)
         if ((int) $this->input('role') === 3) {
            $rules = array_merge($rules, [
                'phone_number' => 'required|string|max:15',
            ]);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('The name is required.'),
            'name.string' => __('The name must be a string.'),
            'name.max' => __('The name may not be greater than :max characters.'),

            'email.required' => __('The email address is required.'),
            'email.unique' => __('The email address already exists.'),
            'email.email' => __('Please enter a valid email address.'),

            'password.required' => __('The password is required.'),
            'password.string' => __('The password must be a string.'),
            'password.min' => __('The password must be at least :min characters.'),
            'password.confirmed' => __('Password confirmation does not match.'),

            'role.required' => __('The role is required.'),
            'role.integer' => __('The role must be an integer.'),
            'role.in' => __('The selected role is invalid.'),

            // Messages for role-specific fields (Author - role 2)
            'phone_number.required' => __('The phone number is required.'),
            'phone_number.string' => __('The phone number must be a string.'),
            'phone_number.max' => __('The phone number may not be greater than :max characters.'),

            'case_keywords.string' => __('Case keywords must be a string.'),
            'case_keywords.max' => __('Case keywords may not be greater than :max characters.'),

            'portfolio_link.url' => __('The portfolio link must be a valid URL.'),
            'portfolio_link.max' => __('The portfolio link may not be greater than :max characters.'),

            'skills.required' => __('Please select at least one skill.'),
            'skills.array' => __('Skills must be an array.'),
            'skills.min' => __('Please select at least one skill.'),
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
