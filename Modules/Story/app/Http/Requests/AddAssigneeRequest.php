<?php

namespace Modules\Story\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddAssigneeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'story_id' => 'required|integer|exists:stories,id',
            'author_id' => 'required|integer|exists:authors,id',
            'role' => 'required|string|in:Illustrator,AV creator',
            'offer_type' => 'required|integer|in:1,2', // 1 for Amount, 2 for Percentage
            'offer_amount' => 'required|numeric|min:0',
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
