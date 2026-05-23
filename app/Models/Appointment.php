<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{

    protected $table='appointments';

    protected $guarded = ['id'];
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function scopeWaitingPayment($query)
    {
        return $query->where('status', 'awaiting_payment');
    }
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
