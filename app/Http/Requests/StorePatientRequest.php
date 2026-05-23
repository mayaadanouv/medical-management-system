<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'gender'=>'required|string|in:male,female',
            'birth_date'=>'required|date|before:-18 years',
            'address'=>'nullable|string',
            'emergency_contact'=>'nullable|string',
            'medical_history'=>'nullable|string',
        ];
    }
}
