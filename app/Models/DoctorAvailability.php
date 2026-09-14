<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorAvailability extends Model
{
    protected $fillable = [
        'doctor_id', 'type', 'day_of_week', 'specific_date',
        'start_time', 'end_time', 'reason',
    ];

    protected $casts = [
        'specific_date' => 'date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
