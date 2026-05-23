<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'patient_id' => $this->id,
            'full_name'  => $this->user->name,
            'email'        => $this->when($this->user->email, $this->user->email),
            'phone_number' => $this->when($this->user->phone, $this->user->phone),
            'gender' => $this->gender == 'male' ? 'male' : 'female',
            'age' => $this->when($this->birth_date, function() {
                return Carbon::parse($this->birth_date)->age;
            }),
            'address'           => $this->when($this->address, $this->address),
            'emergency_contact' => $this->when($this->emergency_contact, $this->emergency_contact),
            'medical_history'   => $this->when($this->medical_history, $this->medical_history),
            'cancellation_count' => $this->when($this->cancellation_count > 0, $this->cancellation_count),
            'appointments' => $this->whenLoaded('appointments', function() {
    return $this->appointments
        // 1. استبعاد المواعيد الملغاة
        ->filter(function($appointment) {
            return $appointment->status !== 'cancelled'; // تأكدي من كتابة كلمة cancelled كما هي في قاعدة البيانات
        })
        // 2. تحويل البيانات للشكل المطلوب
        ->map(function($appointment) {
            return [
                'doctor_name' => $appointment->doctor->user->name,
                'department'  => $appointment->doctor->department->specialty_name, // تأكدي من اسم الحقل specialty_name كما في اليوزر سمري
                'date'        => $appointment->appointment_date,
                'time'        => $appointment->appointment_time,
                'status'      => $appointment->status,
            ];
        })
        // 3. إعادة ترتيب المفاتيح لضمان ظهور مصفوفة صحيحة في JSON
        ->values();
}),
            'is_active'    => (bool)$this->is_active,
            'member_since' => $this->created_at->format('Y-m-d'),
        ];
    }
}
