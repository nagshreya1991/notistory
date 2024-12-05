<?php

namespace Modules\Story\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class AddStoryRequest extends FormRequest
{
     /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
//            'logo' => 'nullable|file|mimes:jpg,jpeg,png',
//            'cover' => 'nullable|file|mimes:jpg,jpeg,png',
//            'period' => 'required|integer|in:1,2,3',
            // Ensure the 'assignees' array is required and has at least one entry.
            'assignees' => 'required|array|min:1',

            // The first 'assignees' entry is required.
            'assignees.0.author_id' => 'required|int',
            'assignees.0.role' => 'required|string',
            'assignees.0.offer_type' => 'required|integer|in:1,2',
            'assignees.0.offer_amount' => 'required|numeric',

            // The remaining 'assignees' entries are optional.
//            'assignees.1.author_id' => 'nullable|int',
//            'assignees.1.role' => 'nullable|string',
//            'assignees.1.offer_type' => 'nullable|integer|in:1,2',
//            'assignees.1.offer_amount' => 'nullable|numeric',
//            'assignees.2.author_id' => 'nullable|int',
//            'assignees.2.role' => 'nullable|string',
//            'assignees.2.offer_type' => 'nullable|integer|in:1,2',
//            'assignees.2.offer_amount' => 'nullable|numeric',
            ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'assignees.required' => 'The assignees field is required.',
            'assignees.array' => 'The assignees must be an array.',
            'assignees.min' => 'The assignees field must have at least one entry.',
            'assignees.0.author_id.required' => 'The author ID for the first assignee is required.',
            'assignees.0.author_id.int' => 'The author ID for the first assignee must be an integer.',
            'assignees.0.role.required' => 'The role for the first assignee is required.',
            'assignees.0.role.string' => 'The role for the first assignee must be a string.',
            'assignees.0.offer_type.required' => 'The offer type for the first assignee is required.',
            'assignees.0.offer_type.integer' => 'The offer type for the first assignee must be an integer.',
            'assignees.0.offer_type.in' => 'The offer type for the first assignee must be either 1 or 2.',
            'assignees.0.offer_amount.required' => 'The offer amount for the first assignee is required.',
            'assignees.0.offer_amount.numeric' => 'The offer amount for the first assignee must be a number.',
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
