<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NotificationSystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Notification;
use Str;

class AppointmentController extends Controller
{
    public function index()
    {
        $appointments = Appointment::with(['doctor.user', 'patient.user'])->get();
        return response()->json([
            'success' => true,
            'message' => 'All Appointments retrieved successfully',
            'data'    => AppointmentResource::collection($appointments)
    ], 200);
    }
    public function show(int $id)
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->find($id);
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
                ], 404);
        }
        return response()->json([
            'success' => true,
            'message' => 'All Appointments retrieved successfully',
            'data'    =>  new AppointmentResource($appointment)
        ], 200);
    }
    public function store(StoreAppointmentRequest $request)
{
    $validated = $request->validated();
    $patient = Auth::user()->patient;

    if (!$patient) {
        return response()->json(['success' => false, 'message' => 'You must be registered as a patient'], 403);
    }

    $patientId = $patient->id;
    $doctorId = $validated['doctor_id'];
    $date = $validated['appointment_date'];
    $requestedTime = Carbon::parse($validated['appointment_time'])->format('H:i:s');

    // جميع الحالات ما عدا الملغى (cancelled)
    $activeStatuses = ['awaiting_payment', 'confirmed', 'suggested', 'pending_approval'];

    // الشرط الأول: يمنع وجود أي موعد "نشط" عند نفس الطبيب (مهما كان التاريخ أو الوقت)
    $hasAnyActiveWithDoctor = Appointment::where('patient_id', $patientId)
        ->where('doctor_id', $doctorId)
        ->whereIn('status', $activeStatuses)
        ->where('appointment_date', '>=', now()->toDateString()) // المواعيد المستقبلية فقط
        ->exists();

    if ($hasAnyActiveWithDoctor) {
        return response()->json([
            'success' => false,
            'message' => 'You already have a pending, confirmed, or suggested appointment with this doctor.'
        ], 400);
    }

    // الشرط الثاني: يمنع وجود أي موعد عند "طبيب آخر" في نفس الوقت تماماً
    $hasTimeConflictAnywhere = Appointment::where('patient_id', $patientId)
        ->where('appointment_date', $date)
        ->where('appointment_time', $requestedTime)
        ->whereIn('status', $activeStatuses)
        ->exists();

    if ($hasTimeConflictAnywhere) {
        return response()->json([
            'success' => false,
            'message' => 'You already have another appointment at the same time with a different doctor.'
        ], 400);
    }

    // --- تكملة التحقق من دوام الطبيب وتوفر الموعد ---
    $shift = $this->getDoctorShift($doctorId, $date);
    $settings = Setting::first();
    $duration = $settings ? (int)$settings->duration : 30;

    if (!$shift) {
        return $this->handleNearestSlotSuggestion($doctorId, $date, $requestedTime, $validated, $patientId, $activeStatuses);
    }

    $isWithinShift = Carbon::parse($requestedTime)->between(
        Carbon::parse($shift->start_time),
        Carbon::parse($shift->end_time)->subMinutes($duration)
    );

    // فحص إذا كان الموعد محجوزاً من مريض آخر
    $isBookedByOther = Appointment::where('doctor_id', $doctorId)
        ->where('appointment_date', $date)
        ->where('appointment_time', $requestedTime)
        ->whereIn('status', $activeStatuses)
        ->exists();

    if ($isWithinShift && !$isBookedByOther) {
        $appointment = $this->createAppointmentRecord($validated, $requestedTime, 'awaiting_payment', $patientId, $validated['reason'] ?? '');
        $admins=User::where('type_user','admin')->get();
        // 2. إرسال الإشعار باستخدام الكلاس الموحد
            Notification::send($admins, new NotificationSystem([
                'title' => 'New Appointment Pending Payment',
                'message' => 'Patient ' . (Auth::user()->name ?? 'Guest') . ' has booked a new appointment with Dr. ' . ($appointment->doctor->user->name ?? 'the selected doctor') . '. Confirmation is pending payment.',
                'type' => 'NEW_APPOINTMENT_ADMIN',
                'url' => '',
            ]));
        return response()->json([
            'success' => true,
            'status'  => 'available',
            'data'    => new AppointmentResource($appointment)
        ], 201);
    }

    // اقتراح أقرب موعد إذا لم يتوفر الموعد المطلوب
    return $this->handleNearestSlotSuggestion($doctorId, $date, $requestedTime, $validated, $patientId, $activeStatuses);
}
    private function handleNearestSlotSuggestion($doctorId, $date, $requestedTime, $validated, $patientId, $activeStatuses)
{
    $nearestSlot = $this->findNearestSlot($doctorId, $date, $requestedTime, $activeStatuses);

    if ($nearestSlot) {
        // التأكد أن الموعد المقترح لا يتضارب مع موعد آخر للمريض عند طبيب مختلف
        $conflictAtSuggestedTime = Appointment::where('patient_id', $patientId)
            ->where('appointment_date', $nearestSlot['date'])
            ->where('appointment_time', $nearestSlot['time'])
            ->whereIn('status', $activeStatuses)
            ->exists();

        if ($conflictAtSuggestedTime) {
            return response()->json([
                'success' => false,
                'message' => 'We found a slot, but you already have an appointment at that time with someone else.'
            ], 400);
        }

        $validated['appointment_date'] = $nearestSlot['date'];
        $appointment = $this->createAppointmentRecord($validated, $nearestSlot['time'], 'suggested', $patientId, $validated['reason'] ?? '');

        return response()->json([
            'success' => true,
            'status'  => 'suggested',
            'message' => 'Suggested appointment created.',
            'data'    => new AppointmentResource($appointment)
        ], 200);
    }

    return response()->json(['success' => false, 'message' => 'No slots found'], 404);
}
//,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,,
private function getDoctorShift(int $doctorId, $date)
{
    $dayName = Carbon::parse($date)->format('l'); // مثل Saturday, Sunday...

    return DB::table('doctors_schedules')
        ->join('schedules', 'doctors_schedules.schedule_id', '=', 'schedules.id')
        ->where('doctors_schedules.doctor_id', $doctorId)
        ->where('schedules.day', $dayName)
        ->whereNull('doctors_schedules.deleted_at') // فحص الحذف في جدول الربط
        ->whereNull('schedules.deleted_at')         // فحص الحذف في جدول الأيام
        ->select('doctors_schedules.start_time', 'doctors_schedules.end_time')
        ->first();
}
//.................................................
private function findNearestSlot(int $doctorId, $date, $time, $activeStatuses)
{
    $currentDate = Carbon::parse($date);
    $patientId = Auth::user()->patient->id;
    $settings = Setting::first();
    $duration = $settings ? (int)$settings->duration : 30;

    // البحث خلال الـ 7 أيام القادمة
    for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
        $targetDate = $currentDate->copy()->addDays($dayOffset);
        $shift = $this->getDoctorShift($doctorId, $targetDate->format('Y-m-d'));

        if (!$shift) continue;

        $startTime = Carbon::parse($shift->start_time);
        $endTime = Carbon::parse($shift->end_time);

        // إذا كان اليوم هو "اليوم الحالي"، نبدأ البحث من بعد الوقت الحالي بـ 15 دقيقة
        if ($targetDate->isToday()) {
            $startLoopTime = Carbon::now()->addMinutes(15);
            if ($startLoopTime->lessThan($startTime)) {
                $startLoopTime = $startTime->copy();
            }
        } else {
            $startLoopTime = $startTime->copy();
        }

        // البحث عن أول Slot متاح في اليوم
        while ($startLoopTime->copy()->addMinutes($duration)->lessThanOrEqualTo($endTime)) {
            $candidateTime = $startLoopTime->format('H:i:s');

            $doctorBusy = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', $targetDate->format('Y-m-d'))
                ->where('appointment_time', $candidateTime)
                ->whereIn('status', $activeStatuses)
                ->exists();

            $patientBusy = Appointment::where('patient_id', $patientId)
                ->where('appointment_date', $targetDate->format('Y-m-d'))
                ->where('appointment_time', $candidateTime)
                ->whereIn('status', $activeStatuses)
                ->exists();

            if (!$doctorBusy && !$patientBusy) {
                return [
                    'date' => $targetDate->format('Y-m-d'),
                    'time' => $candidateTime
                ];
            }
            $startLoopTime->addMinutes($duration);
        }
    }
    return null;
}
private function createAppointmentRecord($data, $time, $status, $patientId, $reason)
{
    return Appointment::create([
        'patient_id'       => $patientId,
        'doctor_id'        => $data['doctor_id'],
        'appointment_date' => $data['appointment_date'],
        'appointment_time' => $time,
        'status'           => $status,
        'payment_token'    => bin2hex(random_bytes(8)),
        'reason'           => $reason,
        'expires_at'       => now()->addHours(2),
    ]);
}

 // جلب المواعيد التي رفع أصحابها الإيصالات ولم تُؤكد بعد
    public function getPendingApprovalAppointment()
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->pendingApproval()->get();
        if ($appointment->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No appointments pending approval at the moment.',
                'data'    => []
            ], 200);
        }
        return response()->json([
            'success' => true,
            'message' => 'Pending approval appointments retrieved successfully.',
            'data' => AppointmentResource::collection($appointment)
        ], 200);
    }
    //جلب المواعيد يلي اصحابها لسا مادفعوو
    public function getWaitingPaymentAppointment()
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->waitingPayment()->get();
        if ($appointment->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No unpaid appointments found at the moment.',
                'data'    => []
            ], 200);
        }
        return response()->json([
            'success' => true,
            'message' => 'Unpaid appointments retrieved successfully.',
            'data' => AppointmentResource::collection($appointment)
        ], 200);
    }
    //جلب المواعيد يلي الادمن اكدهم
    public function getConfirmedAppointment()
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->confirmed()->get();
        if ($appointment->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No confirmed appointments found at the moment.',
                'data'    => []
            ], 200);
        }
        return response()->json([
            'success' => true,
            'message' => 'Confirmed appointments retrieved successfully.',
            'data' => AppointmentResource::collection($appointment)
        ], 200);
    }
     //تابع رفع الاشعار
    public function uploadPaymentReceipt(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $patient = Auth::user()->patient;
        if (!$patient || $appointment->patient_id != $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to modify this appointment.'
            ], 403);
        }
        $validated = $request->validated();
        if ($request->hasFile('payment_receipt')) {
            $path = $request->file('payment_receipt')->store('payment_receipt', 'public');
            $appointment->update(['payment_receipt' => $path]);
            $appointment->payment_receipt = asset('storage/' . $appointment->payment_receipt);
            $appointment->update(['status' => 'pending_approval']);
            $appointment->load(['doctor.user', 'patient.user']);
            $admins = User::where('type_user', 'admin')->get();

            // 2. إرسال الإشعار باستخدام الكلاس الموحد
            Notification::send($admins, new NotificationSystem([
                'title' => 'New Payment Receipt Submitted',
                'message' => 'Patient ' . (Auth::user()->name ?? 'Guest') . ' has uploaded a payment receipt for Appointment ID: #' . $appointment->id . '. Please review the attachment for confirmation.',
                'type' => 'PAYMENT_SUBMITTED',
                'url' => '',
            ]));
            return response()->json([
                'success' => true,
                'status'  => 'pending_approval',
                'message' => 'Payment receipt uploaded successfully. Your appointment is now pending approval.',
                'data'    => new AppointmentResource($appointment)
            ], 200);
        }
        return response()->json([
            'success' => false,
            'message' => 'Error: Payment receipt file is required.',
        ], 400);
    }
    //..................................................
    public function patientCancelAppointment(int $id)
    {
        $appointment =  Appointment::with(['doctor.user', 'patient.user'])->find($id);
        $patient = Auth::user()->patient;

        if (!$appointment ||$appointment->status=='cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found or already cancelled'
                ], 404);
        }
        if (!$patient || $appointment->patient_id !== $patient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
                ], 403);
        }
        $appointmentDateTime = Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);
        $hoursRemaining = now()->diffInHours($appointmentDateTime, false);
        $refundMessage = "";
        if ($appointment->status == 'confirmed') {
        $refundMessage = ($hoursRemaining < 24)
            ? " Note: Fees are non-refundable as the appointment is in less than 24 hours."
            : " The administration will contact you regarding the refund.";
        }
        $patient->increment('cancellation_count');
        $patient->refresh();

        $doctor = $appointment->doctor->user; // تأكدي من علاقة doctor و user
        if ($doctor) {
            $doctor->notify(new NotificationSystem([
                'title' => 'Appointment Cancellation Notice',
                'message' => 'Please be advised that the patient ' . (Auth::user()->name ?? 'Guest') . ' has cancelled their appointment scheduled for ' . ($appointment->appointment_date ?? 'the specified date') . '.',
                'type' => 'APPOINTMENT_CANCELLED_BY_PATIENT',
                'url' => '',
            ]));
        }

        // 2. إرسال إشعار للأدمن (سيصله Database + Pusher)
        $admins = User::where('type_user', 'admin')->get();

        $adminMessage = 'Patient ' . (Auth::user()->name ?? 'Guest') . ' has cancelled their appointment. Current cancellation count: ' . $patient->cancellation_count . '.';

        if ($patient->cancellation_count >= 3) {
            $adminMessage .= ' (Patient account has been automatically suspended).';
        }

        Notification::send($admins, new NotificationSystem([
            'title' => 'Patient Cancellation Alert',
            'message' => $adminMessage,
            'type' => 'PATIENT_CANCELLATION_ALERT',
            'url' => '',
        ]));

        if ($patient->cancellation_count >= 3) {
            $patient->update(['is_active' => false]); // إيقاف الحساب
            $appointment->update(['status' => 'cancelled']);
            return response()->json([
                'success' => false,
                'message' => 'Appointment cancelled, but your account has been deactivated for exceeding the cancellation limit (3 times). Please contact support.',
                'data' => new AppointmentResource($appointment)
            ], 403);
        }
        $appointment->update(['status' => 'cancelled']);
        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully.' . $refundMessage,
            'data' => new AppointmentResource($appointment),
            'cancellations_count' => $patient->cancellation_count
        ], 200);
    }
    //..............................................
    public function doctorCancelAppointment(int $id)
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->find($id);
        $user = Auth::user();
        if (!$appointment ||$appointment->status=='cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.'
                ], 404);
        }
        if ($user->type_user !== 'doctor' || $appointment->doctor_id !== $user->doctor->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.'
                ], 403);
        }
        $appointment->update([
            'status' => 'cancelled'
        ]);

        $patientUser = $appointment->patient->user;
        if ($patientUser) {
            $patientUser->notify(new NotificationSystem([
                'title' => 'Appointment Cancellation - Medical Center',
                'message' => 'We apologize for the inconvenience. Dr. ' . ($user->name ?? 'the doctor') . ' has cancelled your appointment scheduled for ' . ($appointment->appointment_date ?? 'the specified date') . ' due to an emergency. Please contact the center administration to process your refund.',
                'type' => 'DOCTOR_CANCELLED_APPOINTMENT',
                'url' => '',

            ]));
        }

        $admins = User::where('type_user', 'admin')->get();
        Notification::send($admins, new NotificationSystem([
            'title' => 'Doctor Cancellation Alert',
            'message' => 'Dr. ' . ($user->name ?? 'Unknown') . ' has cancelled Appointment ID: #' . $appointment->id . ' associated with Patient: ' . ($patientUser->name ?? 'N/A') . '.',
            'type' => 'ADMIN_DOCTOR_CANCEL_ALERT',
            'url' => '',

        ]));
        return response()->json([
            'success' => true,
            'message' => "Appointment cancelled by the doctor due to an emergency.",
            'data' => new AppointmentResource($appointment)
    ], 200);
    }
    //....................................................
    public function forceDeleteCancelledAppointments()
{

    $deletedCount = Appointment::where('status', 'cancelled')
        ->where('appointment_date', '<', now()->subMonths(6))
        ->delete();

    return response()->json([
        'success' => true,
        'message' => "Old archive cleaned. Total deleted records: {$deletedCount}.",
    ],200);
}
    //...............................
public function cancelEntireDay(Request $request)
    {
        $request->validate([
        'date' => 'required|date|after_or_equal:today'
    ]);
        $doctorUser = Auth::user(); // جلب كائن المستخدم الحالي (الطبيب)
        $doctorId = $doctorUser->doctor->id; // الـ ID لاستخدامه في الاستعلام
        $date = $request->date;
        $appointments = Appointment::with(['doctor.user', 'patient.user'])
            ->where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['awaiting_payment', 'confirmed', 'suggested', 'pending_approval'])
            ->get();
        if ($appointments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No bookings found for this date.'
                ], 404);
        }
        $count = $appointments->count();
        foreach ($appointments as $appointment) {
            $appointment->status = 'cancelled';
            $appointment->save();
            if ($appointment->patient && $appointment->patient->user) {
                $appointment->patient->user->notify(new NotificationSystem([
                    'title' => 'Urgent: Appointment Cancellation Notice',
                    'message' => "We apologize for the inconvenience. Dr. {$doctorUser->name} has cancelled all appointments scheduled for {$date} due to an emergency. Please visit the center administration to process your refund.",
                    'type' => 'DOCTOR_CANCELLED_DAY',
                    'url' => '',
                ]));
            }
        }
        // --- إرسال إشعار واحد للأدمن يلخص العملية ---
        $admins = User::where('type_user', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NotificationSystem([
                'title' => 'Full Workday Cancellation Alert',
                'message' => "Dr. {$doctorUser->name} has cancelled all appointments scheduled for {$date}. Total affected appointments: {$count}.",
                'type' => 'ADMIN_DOCTOR_DAY_CANCELLED',
                'url' => '',
            ]));

        }
        return response()->json([
            'success' => true,
            'message' => "All appointments on {$date} have been cancelled ({$count} appointments).",
        ], 200);
    }
    //..........................................
    public function getCancelledAppointment()
    {
        $appointment = Appointment::with(['doctor.user', 'patient.user'])->cancelled()->get();
        if ($appointment->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No archived appointments found.',
                'data' => []
            ], 200);
        }
        return response()->json([
            'success' => true,
            'message' => 'Cancelled appointments retrieved successfully.',
            'data' => AppointmentResource::collection($appointment)
        ], 200);
    }
}
