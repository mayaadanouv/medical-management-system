<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
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
            'department_id'=>'required|integer|exists:departments,id',
            'syndicate_number'=>'required|string|unique:doctors,syndicate_number',
            'certificate_image'=>'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'experience_years'=>'required|integer|min:0',
            'bio'=>'nullable|string',
            'clinic_location'=>'nullable|string',
            'whatsapp_url'=>'nullable|url',
            'profile_image'=>'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ];
    }
}
