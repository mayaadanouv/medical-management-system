<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'doctor_id'    => $this->id,
            'doctor_name'  => $this->user->name ,
            'work_schedules' => $this->whenLoaded('schedules', function() {
            return $this->schedules->filter(function($schedule) {
                return is_null($schedule->pivot->deleted_at);
            })->map(function($schedule) {
                return [
                    'day'        => $schedule->day,
                    'start_time' => $schedule->pivot->start_time,
                    'end_time'   => $schedule->pivot->end_time,
                ];
            })->values();
}),
        ];
    }
}
