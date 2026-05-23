<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\DoctorScheduleController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// --- Public Routes (بدون حماية) ---
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);
// --- Protected Routes (تحتاج توكن Sanctum) ---
Route::post('logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
// --- 1. Departments (الأقسام) ---
    Route::prefix('department')->group(function () {
        Route::middleware('auth:sanctum')->group(function () {
        Route::middleware(['is_admin'])->group(function () {
            //فقط الادمن
            Route::get('/archived', [DepartmentController::class, 'archived']);//عرض الاقسام التي تم ارشفتها
            Route::post('/store', [DepartmentController::class, 'store']);//تخزين الاقسام من قبل الادمن
            Route::put('/update/{id}', [DepartmentController::class, 'update']);//تعديل بيانات القسم من قيل الادمن
            Route::post('/restore/{id}', [DepartmentController::class, 'restore']);//استعادة قسم من الارشيف من قبل الادمن
            Route::post('/archive/{id}', [DepartmentController::class, 'archive']);//ارشفت قسم
            Route::delete('/delete/{id}', [DepartmentController::class, 'destroy']);//خذف نهائي للقسم
        });});
        //للجميع
        Route::get('', [DepartmentController::class, 'index']);//عرض الاقسام الموجودة في المركز
        Route::get('/{id}', [DepartmentController::class, 'show']);//عرض قسم محدد
    });

    // --- 2. Doctors (الأطباء) ---
    Route::prefix('doctor')->group(function (){
    Route::middleware('auth:sanctum')->group(function (){
        // عمليات الطبيب على حسابه الخاص
        Route::post('/store', [DoctorController::class, 'store'])->middleware('is_doctor');//اكمال عملية تخزين بيانات الطبيب
        Route::post('/update', [DoctorController::class, 'update'])->middleware('approved_doctor');//تعديل بيانات طبيب
    });

    // عرض عام للأطباء
        Route::get('', [DoctorController::class, 'index']);//عرض الاطباء
        Route::get('/{id}', [DoctorController::class, 'show']);//عرض  طبيب محدد
    });
    // عرض الأطباء حسب القسم
    Route::get('/departments/{id}/doctors', [DoctorController::class, 'getDoctorsByDepartment']);

    // --- 3. Patients (المرضى) ---
    Route::prefix('patient')->group(function () {
        Route::middleware('auth:sanctum')->group(function (){
        Route::post('/store', [PatientController::class, 'store'])->middleware('is_patient');//اكمال عملية تخزين بيانات مريض
        Route::middleware(['active_patient'])->group(function () {
            Route::put('/update', [PatientController::class, 'update']);//تعديل بيانات مريض
        });

        // عرض ملف المريض (للطبيب أو الإدمن)
        Route::get('/{id}', [PatientController::class, 'show'])->middleware('admin-or-doctor-or-Owner');
    });});

    // --- 4. Schedules (مواعيد الأطباء) ---
    Route::prefix('doctors/schedules')->group(function () {
        Route::middleware('auth:sanctum')->group(function (){
        Route::middleware(['approved_doctor'])->group(function () {
            Route::post('', [DoctorScheduleController::class, 'store']);//تخزين موعد دوام طبيب
            Route::put('/{schedule_id}', [DoctorScheduleController::class, 'update']);//تعديل المواعيد للظوام الطبيب
            Route::delete('/{schedule_id}', [DoctorScheduleController::class, 'destroy']);//حذف موعد دوام
        });

        Route::get('/{doctor_id}', [DoctorScheduleController::class, 'show'])->middleware('active_patient');// رؤية المواعيد متاحة للمريض النشط
        Route::get('', [DoctorScheduleController::class, 'index'])->middleware('is_admin');//عرض كل مواعيد الاطباء للادمن
    });});

    // --- 5.  appointments (الحجوزات )
Route::prefix('appointments')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('patient')->group(function(){
            Route::middleware('active_patient')->group(function () {
                Route::post('/store', [AppointmentController::class, 'store']);//حجز موعد من قبل مريض
                Route::post('/PaymentReceipt/{appointment}', [AppointmentController::class, 'uploadPaymentReceipt']);//رفع اشعار الدفع
                Route::put('/delete_appointment/{id}', [AppointmentController::class, 'patientCancelAppointment']);//الغاء موعد من قبل المريض
            });
        });
        Route::put('doctor/delete_appointment/{id}', [AppointmentController::class, 'doctorCancelAppointment'])->middleware('approved_doctor');//الغاء الموعد من قبل
        Route::put('doctor/cancel_day', [AppointmentController::class, 'cancelEntireDay'])->middleware('approved_doctor');//الغاء مواعيد يوم كامل من قبل الطبيب
    });
});

    // --- 6. Admin Dashboard (لوحة تحكم المسؤول) ---
    Route::prefix('admin')->middleware('is_admin')->group(function () {
        Route::middleware('auth:sanctum')->group(function (){
            Route::post('/settings/update', [SettingController::class, 'updateSettings']);
            Route::post('/change_Password', [UserController::class, 'changePassword']);

        // إدارة شؤون الأطباء
        Route::prefix('doctors')->group(function () {
            Route::get('/pending', [DoctorController::class, 'getPendingDoctors']);//عرض الاطباء قيد الانتظار
            Route::get('/archived', [DoctorController::class, 'indexArchived']);//عرض الاطباء يلي بالارشيف
            Route::get('/rejected',[DoctorController::class,'getrejectedDoctor']);//عرض الاطباء المرفوضين
            Route::put('/approve/{id}', [AdminController::class, 'approveDoctor']);//قبول طبيب
            Route::put('/reject/{id}', [AdminController::class, 'rejectDoctor']);//رفض طبيب
            Route::post('/archive/{id}', [DoctorController::class, 'archive']);//ارشفت طبيب
            Route::post('/restore/{id}', [DoctorController::class, 'restore']);//استعادة طبيب من الارشيف
            Route::delete('/force-delete/{id}', [DoctorController::class, 'destroy']);//حذف طبيب من الارشيف
        });

        // إدارة شؤون المرضى
        Route::prefix('patients')->group(function () {
            Route::get('', [PatientController::class, 'index']);//عرض كل المرضى
            Route::get('/archived', [PatientController::class, 'indexArchived']);//عرض المرضى يلي بالارشيف
            Route::get('/disabled', [PatientController::class, 'getDisabledPatient']);//عرض المرضى يلي حساباتهم معطلة
            Route::put('/deactivate/{id}', [AdminController::class, 'accountDeactivation']);//تعطيل حساب مريض
            Route::put('/activate/{id}', [AdminController::class, 'accountActivation']);//تفعيل حساب مريض
            Route::post('/archive/{id}', [PatientController::class, 'archive']);//ارشفت مريض
            Route::post('/restore/{id}', [PatientController::class, 'restore']);//استعادة مريض من الارشيف
            Route::delete('/force-delete/{id}', [PatientController::class, 'destroy']);//حذف مريض من الارشيف
        });
        //إدارة الحجوزات
        Route::prefix('appointments')->group(function () {
            Route::put('/confirmed/{id}', [AdminController::class, 'confirmAppointment']);//الادمن يؤكد الحجز بعد رفع الاشعار
            Route::get('/pending_approval', [AppointmentController::class, 'getPendingApprovalAppointment']);//عرض الحجوزات يلي ناطرة الادمن ياكدها
            Route::get('/waiting_payment', [AppointmentController::class, 'getWaitingPaymentAppointment']);//عرض الحجوزات يلي لسا المريض ما دفع فيها الرسوم
            Route::get('/confirmed', [AppointmentController::class, 'getConfirmedAppointment']);//عرض الحجوزات يلي تم تاكيدها من قبل الادمن
            Route::delete('/delete', [AppointmentController::class, 'forceDeleteCancelledAppointments']);//خذف الخجوزات يلي مر فترة على الغاءها
            Route::get('/cancelled', [AppointmentController::class, 'getCancelledAppointment']);//عرض الحجوزات يلي تم ارشفتها من قبل الادمن
        });
    });
});
