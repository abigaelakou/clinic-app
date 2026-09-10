<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicalRecordAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['patient_id', 'user_id', 'action', 'accessed_at'];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
