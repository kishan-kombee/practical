<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AppointmentUpdateRequest extends FormRequest
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
            'patient_name' => 'required|string|max:50|regex:/^[a-zA-Z\s]+$/',
            'clinic_location' => 'required|string|max:200',
            'clinician_id' => 'required|exists:users,id,deleted_at,NULL,status,Y',
            'appointment_date' => 'required|date',
            'status' => 'required|in:B,D,N',
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
