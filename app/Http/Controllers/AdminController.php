<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Notifications\NotificationSystem;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function approveDoctor(int $id) //قبول طبيب
{
    $doctor = Doctor::findOrFail($id);
    $doctor->update(['status' => 'approved']);
    $user = $doctor->user; // الوصول للمستخدم المرتبط بالطبيب
        if ($user) {
            $user->notify(new NotificationSystem([
                'title' => 'Account Approval Notification',
                'message' => 'Your request to join our medical center has been approved. Welcome to the team, Dr. ' . ($user->name ?? '') . '!',
                'type' => 'ACCOUNT_APPROVED',
                'url' => '',
            ]));
        }
    return response()->json([
        'success' => true,
        'message' => 'Doctor accont approved successfully'
        ]);
}
public function rejectDoctor(int $id)//رفض طبيب
{
        $doctor = Doctor::findOrFail($id);
        $doctor->update(['status' => 'rejected']);
        return response()->json([
            'success' => true,
            'message' => 'Doctor rejected successfully'
            ]);
}
public function accountDeactivation(int $id) //تعطيل حساب مريض
{
    $patient=Patient::findOrFail($id);
    $patient->update(['is_active'=>false]);
    return response()->json([
        'success' => true,
        'message' => 'The account has been successfully disabled'
        ]);
}
public function accountActivation(int $id)//تفعيل حساب طبيب
{
    $patient=Patient::findOrFail($id);
    $patient->update(['is_active'=>true]);
    return response()->json([
        'success' => true,
        'message' => 'The account has been successfully activated'
        ]);
}
public function confirmAppointment( int $id)//تاكيد الحجز بعد الدفع
{
    $appointment = Appointment::findOrFail($id)->where('status', 'pending_approval')->first();;
    $appointment->update([
        'status' => 'confirmed'
    ]);
    $patient = $appointment->patient->user; // أو $appointment->patient حسب تسمية العلاقة عندك

        // 2. إرسال الإشعار للمريض
        if ($patient) {
            $patient->notify(new NotificationSystem([
                'title' => 'Appointment Confirmation',
                'message' => 'Your appointment has been successfully confirmed by the administration. We wish you a speedy recovery.',
                'type' => 'APPOINTMENT_CONFIRMED',
                'url' => '',
            ]));
        }
        $doctorUser = $appointment->doctor->user; // الوصول للمستخدم المرتبط بالطبيب
        if ($doctorUser) {
            $doctorUser->notify(new NotificationSystem([
                'title' => 'New Confirmed Appointment',
                'message' => 'A new appointment has been confirmed for the patient: ' . ($patient->name ?? 'Patient') . ' on ' . ($appointment->appointment_date ?? ''),
                'type' => 'NEW_CONFIRMED_APPOINTMENT',
                'url' => '',
            ]));
        }
    return response()->json([
        'success' => true,
        'message' => 'The Appointment confirmed successfully'
        ], 200);
}
}

