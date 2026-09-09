<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;
    protected $dates=['deleted_at'];
    protected $table='patients';
    protected $guarded=['id'];
    //ظاهر بأن هناك حقلاً اسمه age عند إرسال البيانات
    protected $appends = ['age'];
    // هذا التابع "يصنع" حقل العمر برمجياً
    public function getAgeAttribute()
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function doctors()
{
    // جلب الأطباء عبر جدول المواعيد (الحجوزات) كجدول وسيط
    return $this->belongsToMany(Doctor::class, 'appointments', 'patient_id', 'doctor_id')
                ->distinct(); // لمنع تكرار الطبيب في القائمة إذا كان هناك أكثر من موعد معه
}

}
