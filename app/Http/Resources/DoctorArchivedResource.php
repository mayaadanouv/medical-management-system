<?php

namespace App\Http\Resources;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorArchivedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();

        // جلب المستخدم الخاص بالطبيب حتى لو كان مؤرشفاً لتفادي الأخطاء
        $doctorUser = $this->user()->withTrashed()->first();

        // التحقق من الصلاحيات بأمان
        $canViewPrivateData = $user && ($user->type_user === 'admin' || ($doctorUser && $user->id === $doctorUser->id));

        return [
            'doctor_id'        => $this->id,
            'doctor_name'      => $doctorUser ? $doctorUser->name : null, // جلب الاسم بأمان
            'specialization'   => $this->department?->specialty_name, // استخدام ?-> للأمان في حال عدم وجود قسم
            'experience_years' => $this->experience_years,
            'bio'              => $this->when($this->bio, $this->bio),
            'clinic_location'  => $this->when($this->clinic_location, $this->clinic_location),
            'whatsapp_url'     => $this->when($this->whatsapp_url, $this->whatsapp_url),
            'profile_image'    => $this->profile_image
                ? asset('storage/' . $this->profile_image)
                : null,
            'syndicate_number' => $canViewPrivateData ? $this->syndicate_number : null,
            'certificate_image'=> $canViewPrivateData ? ($this->certificate_image ? asset('storage/' . $this->certificate_image) : null) : null,
            'work_schedules'   => $this->whenLoaded('schedules', function() {
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
            'status'           => $this->status,
            'created_at'       => $this->created_at?->format('Y-m-d'),
        ];
    }
}
