<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vital extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'patient_id', 'recorded_by', 'consultation_id',
        'weight_kg', 'height_cm', 'blood_pressure', 'temperature_c', 'heart_rate',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($vital) {
            $vital->recorded_at = $vital->recorded_at ?? now();
        });
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
