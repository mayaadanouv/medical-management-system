<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;
    protected $table='departments';
    protected $dates=['deleted_at'];
    protected $guarded=['id'];
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
}
