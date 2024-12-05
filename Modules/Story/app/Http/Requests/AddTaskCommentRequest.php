<?php

namespace Modules\Story\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddTaskCommentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'story_task_id' => 'required|exists:story_tasks,id',
            'author_id' => 'required|exists:authors,id',
            'comment' => 'required|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpeg,png,jpg,gif,bmp,doc,docx,txt',
           // 'parent_id' => 'nullable|exists:task_comments,id',
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
