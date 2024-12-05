<?php

namespace Modules\Story\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddStoryPageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'story_id' => 'required|exists:stories,id',
            'page_number' => 'nullable|integer',  // Auto-numbering, so nullable
            'title' => 'nullable|string|max:255',
            'pitch_line' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'author_note' => 'nullable|string',
            'content' => 'nullable|string',  // Allow content to be nullable
            'status' => 'nullable|integer|in:0,1,2', // Default status to 1 if not provided
           // 'launch_sequence' => 'required|integer|min:1',
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
