<?php

namespace App\Http\Resources;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();
        $canSeeNotes = $user && (
            ($user->type_user === 'doctor' && $this->doctor_id === $user->doctor?->id) ||
            ($user->type_user === 'patient' && $this->patient_id === $user->patient?->id)
        );

        $activeStatuses = ['awaiting_payment', 'confirmed', 'pending_approval', 'completed'];
        return [
        'id'               => $this->id,
        'status'           => $this->status,
        'booking_dettails'=>$this->when(in_array($this->status ,$activeStatuses),[
            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
        ]),
        'suggestion' => $this->when($this->status === 'suggested',[
            'suggested_date'=> $this->appointment_date,
            'suggested_time'=>$this->appointment_time,
        ]),
        'doctor_notes' => $this->when($canSeeNotes, $this->doctor_notes),
        'visit_count' => $this->when($canSeeNotes, $this->visit_count),
        'doctor' => [
            'id'   => $this->doctor->id,
            'name' => $this->doctor->user->name
        ],
        'patient' => [
            'id'   => $this->patient->id,
            'name' => $this->patient->user->name
        ],
            'payment_token' =>$this->payment_token,
            'reason' => $this->reason,
            'expires_at' =>$this->expires_at,
    ];
    }
}
