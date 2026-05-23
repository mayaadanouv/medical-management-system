<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use SoftDeletes;
    protected $dates=['deleted_at'];
    protected $table='schedules';
    protected $guarded=['id'];
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class,'doctors_schedules')->using(DoctorSchedule::class)
        ->withPivot('start_time','end_time')->withTimestamps();
    }
}
