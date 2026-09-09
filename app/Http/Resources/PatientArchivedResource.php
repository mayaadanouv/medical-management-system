<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientArchivedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // جلب بيانات المستخدم حتى لو كان مؤرشفاً لتفادي خطأ الـ null
        $patientUser = $this->user()->withTrashed()->first();

        return [
            'patient_id'         => $this->id,
            'full_name'          => $patientUser ? $patientUser->name : null,
            'email'              => $patientUser ? $patientUser->email : null,
            'phone_number'       => $patientUser ? $patientUser->phone : null,
            'gender'             => $this->gender == 'male' ? 'male' : 'female',
            'age'                => $this->when($this->birth_date, function() {
                return Carbon::parse($this->birth_date)->age;
            }),
            'address'            => $this->when($this->address, $this->address),
            'emergency_contact'  => $this->when($this->emergency_contact, $this->emergency_contact),
            'medical_history'    => $this->when($this->medical_history, $this->medical_history),
            'cancellation_count' => $this->when($this->cancellation_count > 0, $this->cancellation_count),
            'is_active'          => (bool)$this->is_active,
            'member_since'       => $this->created_at?->format('Y-m-d'),
        ];
    }
}
