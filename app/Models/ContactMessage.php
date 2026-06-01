<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $table='contact_messages';

    protected $fillable = ['name', 'email', 'phone', 'department_id', 'message'];

    public function department()
    {
        return $this->belongsTo(Department::class)->withTrashed();
    }
}
