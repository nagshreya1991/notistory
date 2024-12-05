<?php

namespace Modules\Story\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EditStoryPageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'page_id' => 'required|exists:story_pages,id',
          // 'story_id' => 'required|exists:stories,id',
            'page_number' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
            'pitch_line' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'author_note' => 'nullable|string',
            //'content' => 'nullable|string',
            'content' => 'nullable|string|max:16777215',
            'status' => 'nullable|integer|in:0,1,2', // Default values for status
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
