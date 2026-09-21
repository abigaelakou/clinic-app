<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'consultation_type_id', 'appointment_id', 'consulted_at',
        'motif', 'diagnostic', 'prescriptions', 'exams_requested',
        'exam_results', 'is_teleconsultation',
    ];

    protected $casts = [
        'consulted_at' => 'datetime',
        'is_teleconsultation' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function type()
    {
        return $this->belongsTo(ConsultationType::class, 'consultation_type_id');
    }

    public function documents()
    {
        return $this->hasMany(MedicalDocument::class);
    }
}
