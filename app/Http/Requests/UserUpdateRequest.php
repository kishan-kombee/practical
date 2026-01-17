<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserUpdateRequest extends FormRequest
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
            'role_id' => 'sometimes|exists:roles,id,deleted_at,NULL',
            'first_name' => 'sometimes|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'last_name' => 'sometimes|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'email' => 'sometimes|email|max:320',
            'mobile_number' => 'sometimes|digits:10|regex:/^[6-9]\d{10}$/',
            'status' => 'sometimes|string|in:Y,N',
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
