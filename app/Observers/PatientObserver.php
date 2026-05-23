<?php

namespace App\Observers;

use App\Models\Patient;

class PatientObserver
{

    /**
     * Handle the Patient "deleted" event.
     */
    public function deleted(Patient $patient): void
    {
        if (!$patient->isForceDeleting()) {
            // 1. إلغاء المواعيد المستقبلية للمريض
            $patient->appointments()
                ->where('appointment_date', '>=', now()->toDateString())
                ->whereIn('status', ['confirmed', 'awaiting_payment', 'pending_approval'])
                ->update([
                    'status' => 'cancelled',
                ]);

            // 2. أرشفة حساب المستخدم
            $patient->user?->delete();
        }
    }

    /**
     * Handle the Patient "restored" event.
     */
    public function restored(Patient $patient): void
    {
         // استعادة حساب المستخدم المرتبط لتمكينه من الدخول مرة أخرى
        if ($patient->user()->withTrashed()->exists()) {
            $patient->user()->withTrashed()->restore();
        }
    }

    /**
     * Handle the Patient "force deleted" event.
     */

    public function deleting(Patient $patient): void
    {
        if ($patient->isForceDeleting()) {

            $patient->appointments()
                ->where('appointment_date', '>=', now()->toDateString())
                ->whereIn('status', ['confirmed', 'awaiting_payment', 'pending_approval'])
                ->update([
                    'status' => 'cancelled',
                ]);
            if ($patient->user()->withTrashed()->exists())
                {
                $patient->user()->withTrashed()->forceDelete();
                }
        }
    }
    }
