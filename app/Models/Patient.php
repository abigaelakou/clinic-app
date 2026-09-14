<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'date_of_birth', 'sex',
        'phone', 'email', 'address', 'emergency_contact_name',
        'emergency_contact_phone', 'declared_history', 'created_via',
        'created_by', 'identity_verified',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'identity_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }

    public function documents()
    {
        return $this->hasMany(MedicalDocument::class);
    }

    public function sharedDocuments()
    {
        return $this->hasMany(MedicalDocument::class)->where('shared_with_patient', true);
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function age(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    public function logAccess(User $user, string $action = 'view'): void
    {
        $this->accessLogs()->create([
            'user_id' => $user->id,
            'action' => $action,
            'accessed_at' => now(),
        ]);
    }

    public function accessLogs()
    {
        return $this->hasMany(MedicalRecordAccessLog::class);
    }
}
