<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
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
        return
        [
            'name'=>'sometimes|string|max:255',
            'department_id'=>'sometimes|integer',
            'experience_years'=>'sometimes|integer|min:0',
            'bio'=>'sometimes|string',
            'clinic_location'=>'sometimes|string',
            'whatsapp_url'=>'sometimes|string',
            'profile_image'=>'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048'
        ];
    }
}
