<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'item_code' => 'required|max:191',
            'name' => 'required|max:191',
            'price' => 'required',
            'description' => 'required',
            'category_id' => 'required|exists:categories,id,deleted_at,NULL',
            'sub_category_id' => 'required|exists:sub_categories,id,deleted_at,NULL',
            'available_status' => 'required|in:0,1',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Add custom validation messages here
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => __('messages.api.validation_errors'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
