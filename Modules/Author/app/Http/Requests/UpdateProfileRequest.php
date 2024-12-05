<?php namespace Modules\Author\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
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
    public function rules(): array
    {
        
        $rules = [
             'name' => 'nullable|string|max:255',
          //  'email' => 'nullable|email|unique:users,email,' . $this->user()->id,
           // 'phone_number' => 'nullable|string|max:15',
             // These fields are also optional now
             'phone_number' => 'nullable|string|max:15',
             'about' => 'nullable|string|max:1000',
             'case_keywords' => 'nullable|string|max:255',
             'portfolio_link' => 'nullable|url',
             'skills' => 'nullable|array',
            'skills.*' => 'integer|exists:skills,id',
           
        ];

        return $rules;
    }
    public function messages(): array
   {
    return [
        'name.string' => __('The name must be a valid string.'),
        'name.max' => __('The name must not exceed 255 characters.'),
        
        'phone_number.string' => __('The phone number must be a valid string.'),
        'phone_number.max' => __('The phone number must not exceed 15 characters.'),
        
        'about.string' => __('The about section must be a valid string.'),
        'about.max' => __('The about section must not exceed 1000 characters.'),
        
        'case_keywords.string' => __('The case keywords must be a valid string.'),
        'case_keywords.max' => __('The case keywords must not exceed 255 characters.'),
        
        'portfolio_link.url' => __('The portfolio link must be a valid URL.'),
        
        'skills.array' => __('The skills must be an array.'),
        'skills.*.integer' => __('Each skill must be a valid integer.'),
        'skills.*.exists' => __('The selected skill is invalid.')
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