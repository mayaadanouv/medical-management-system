<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-hourly-reminders';
    protected $description = 'Send reminder notifications to patients whose appointments are in 2 hours.';

    public function handle()
    {
        $nowTime = now()->format('H:i:s');
        $twoHoursHence = now()->addHours(2)->format('H:i:s');
        $today = now()->toDateString();

        $appointments = Appointment::with(['patient.user', 'doctor.user'])
            ->where('appointment_date', $today)
            ->whereTime('appointment_time', '>=', $nowTime)
            ->whereTime('appointment_time', '<=', $twoHoursHence)
            ->where('status', 'confirmed')
            ->get();

        if ($appointments->isEmpty()) {
            $this->info("No appointments found between {$nowTime} and {$twoHoursHence}.");
            return;
        }

        foreach ($appointments as $appointment) {
            /** @var Appointment $appointment */

            $cacheKey = "appointment_reminder_{$appointment->id}";
            if (Cache::has($cacheKey)) {
                continue;
            }
            if ($appointment->patient && $appointment->patient->user) {
                $patientUser = $appointment->patient->user;

                // تجهيز المصفوفة
                $doctorName = $appointment->doctor->user->name ?? 'the Doctor';
                $details = [
                    'title' => 'Appointment Reminder',
                    'message' => "Friendly reminder: Your appointment with Doctor ({$doctorName}) is in 2 hours. Please be on time.",
                    'appointment_id' => $appointment->id
                ];

                try {
                    $patientUser->notify(new AppointmentReminderNotification($details));

                    Cache::put($cacheKey, true, now()->addHours(3));
                    $this->info("Reminder sent and saved via Laravel for appointment ID: {$appointment->id}");

                } catch (\Exception $e) {
                    Log::warning("Laravel Notification failed (probably Pusher timeout). Saving directly to DB layout. Error: " . $e->getMessage());

                    \Illuminate\Support\Facades\DB::table('notifications')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'type' => 'App\Notifications\AppointmentReminderNotification',
                        'notifiable_type' => get_class($patientUser),
                        'notifiable_id' => $patientUser->id,
                        'data' => json_encode($details),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Cache::put($cacheKey, true, now()->addHours(3));
                    $this->info("Pusher connection timed out, but reminder was SAVED successfully to Database for appointment ID: {$appointment->id}");
                }
            }
        }
    }
}
