<?php

namespace App\Observers;

use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorObserver
{

    /**
     * Handle the Doctor "deleted" event.
     */
public function deleted(Doctor $doctor): void
{
    if (!$doctor->isForceDeleting()) {

        $doctor->appointments()
            ->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'awaiting_payment', 'pending_approval'])
            ->update([
                'status' => 'cancelled',
            ]);

        if ($doctor->user) {
            $doctor->user()->delete();
        }

        DB::table('doctors_schedules')
            ->where('doctor_id', $doctor->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);
    }
}

    /**
     * Handle the Doctor "restored" event.
     */
    public function restored(Doctor $doctor): void
    {

        $doctor->user()->withTrashed()->restore();

        DB::table('doctors_schedules')
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);
    }

    /**
     * Handle the Doctor "force deleted" event.
     */
    public function deleting(Doctor $doctor): void
    {
        if ($doctor->isForceDeleting()) {
            $doctor->appointments()
            ->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['confirmed', 'awaiting_payment', 'pending_approval'])
            ->update([
                'status' => 'cancelled',
            ]);

        DB::table('doctors_schedules')->where('doctor_id', $doctor->id)->delete();

        if ($doctor->profile_image) {
            Storage::disk('public')->delete($doctor->profile_image);
        }
        if ($doctor->certificate_image) {
            Storage::disk('public')->delete($doctor->certificate_image);
        }
    }
    }
    public function forceDeleted(Doctor $doctor)
    {
    if ($doctor->user()->withTrashed()->exists())
        {
        $doctor->user()->withTrashed()->forceDelete();
        }
    }
    }

