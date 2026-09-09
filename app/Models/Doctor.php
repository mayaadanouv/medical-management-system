<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use SoftDeletes;
    protected $table='doctors';
    protected $guarded=['id'];
    protected $dates=['deleted_at'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function schedules()
{
    return $this->belongsToMany(Schedule::class, 'doctors_schedules')
                ->using(DoctorSchedule::class)
                ->withPivot('id', 'start_time', 'end_time', 'deleted_at')
                ->wherePivotNull('deleted_at')
                ->withTimestamps();
}
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
 // سكوب للأطباء المقبولين
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    //سكوب للاطباء المرفوضين
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
    // سكوب للأطباء الذين ينتظرون الموافقة
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    public function patients()
{
    // جلب المرضى عبر جدول المواعيد (الحجوزات) كجدول وسيط
    return $this->belongsToMany(Patient::class, 'appointments', 'doctor_id', 'patient_id')
                ->distinct(); // لمنع تكرار المريض
}
}
