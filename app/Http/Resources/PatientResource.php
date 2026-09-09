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
        $currentUser = auth()->user();
        $isPatientThemself = $currentUser && $currentUser->id === $this->user_id;

        return [
            'patient_id' => $this->id,
            'full_name'  => $this->user?->name,
            'email'        => $this->when($this->user->email, $this->user->email),
            'phone_number' => $this->when($this->user->phone, $this->user->phone) ,
            'gender' => $this->gender == 'male' ? 'male' : 'female',
            'age' => $this->when($this->birth_date, function() {
                return Carbon::parse($this->birth_date)->age;
            }),
            'address'           => $this->when($this->address, $this->address),
            'emergency_contact' => $this->when($this->emergency_contact, $this->emergency_contact),
            'medical_history'   => $this->when($this->medical_history, $this->medical_history),
            'cancellation_count' => $this->when($this->cancellation_count > 0, $this->cancellation_count),

            'appointments' => $this->whenLoaded('appointments', function() use ($currentUser) {
                return $this->appointments
                    ->filter(function($appointment) {
                        return $appointment->status !== 'cancelled';
                    })
                    ->map(function($appointment) use ($currentUser) {
                        $isCurrentDoctor = $currentUser && $currentUser->id === $appointment->doctor->user_id ;
                        $isPatientThemself = $currentUser && $currentUser->id === $this->user_id;
                        $appointmentData = [
                            'doctor_name' => $appointment->doctor->user->name,
                            'department'  => $appointment->doctor->department->specialty_name,
                            'date'        => $appointment->appointment_date,
                            'time'        => $appointment->appointment_time,
                            'status'      => $appointment->status,
                        ];
                        if ($isCurrentDoctor || $isPatientThemself) {
                            $appointmentData['doctor_notes'] = $appointment->doctor_notes;
                            $appointmentData['visit_count'] = $appointment->visit_count;
                            $appointmentData['reason'] = $appointment->reason;
                        }
                        return $appointmentData;
                    })
                    ->values();
            }),
            'is_active'    => (bool)$this->is_active,
            'member_since' => $this->created_at->format('Y-m-d'),
        ];
    }
}
