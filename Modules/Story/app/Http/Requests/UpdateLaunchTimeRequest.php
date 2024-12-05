<?php

namespace Modules\Story\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLaunchTimeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'story_id' => 'required|exists:stories,id',
            'page_id' => 'required|exists:story_pages,id',
            'author_notes' => 'nullable|string|max:255',
            'launch_status' => 'required|in:1', // launch_status is set to 1 (checked) as per your request
            'launch_sequence' => 'required|integer|min:1',
            'launch_time' => 'nullable|date_format:H:i:s',
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
