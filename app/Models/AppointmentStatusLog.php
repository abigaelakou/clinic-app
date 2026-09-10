<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentStatusLog extends Model
{
    protected $fillable = ['appointment_id', 'from_status', 'to_status', 'changed_by', 'reason'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
