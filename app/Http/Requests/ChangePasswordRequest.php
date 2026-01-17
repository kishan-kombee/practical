<?php

namespace App\Http\Requests;

use Auth;
use Hash;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules()
    {
        return [
            'old_password' => ['required', function ($attribute, $value, $fail) {
                /** @var \App\Models\User|null $user */
                $user = Auth::user();

                if (! $user || ! Hash::check($value, $user->password)) {
                    return $fail(__('The Old password is incorrect.'));
                }
            }],
            'new_password' => 'required|required_with:confirm_password|same:confirm_password|min:6|max:191|different:old_password',
            'confirm_password' => 'required|min:6|max:191',
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
