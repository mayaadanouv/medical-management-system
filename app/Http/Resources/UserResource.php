<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
{
    return [
        'id'         => $this->id,
        'full_name'  => $this->name,
        'email'      => $this->email,
        'phone'      => $this->phone,
        'role'       => $this->type_user,
        'doctorId'   => ($this->type_user === 'doctor' && $this->doctor) ? $this->doctor->id : null,
        'patientId'  => ($this->type_user === 'patient' && $this->patient) ? $this->patient->id : null,
        'joined_at'  => $this->created_at ? $this->created_at->format('Y-m-d') : null,
    ];
}
}
