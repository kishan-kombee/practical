<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        $rules = [
            'email' => 'required|max:191',
            'password' => 'required|min:6|max:191',
        ];

        return $rules;
    }

    /**
     * Get the validation messages apply to this request.
     */
    public function messages()
    {
        return [];
    }
}
