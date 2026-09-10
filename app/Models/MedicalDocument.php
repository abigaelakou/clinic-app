<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalDocument extends Model
{
    protected $fillable = [
        'patient_id', 'consultation_id', 'uploaded_by',
        'title', 'type', 'file_path', 'shared_with_patient',
    ];

    protected $casts = [
        'shared_with_patient' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}
