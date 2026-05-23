<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoctorSchedule extends Pivot
{
    use SoftDeletes;
    protected $table='doctors_schedules';
    protected $dates=['deleted_at'];
    protected $guarded=['id'];

    protected static function booted()
{
    static::addGlobalScope('softDeletes', function ($builder) {
        $builder->whereNull('deleted_at');
    });
}
}
