<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'patient_id', 'doctor_id', 'specialty_id', 'scheduled_at',
        'duration_minutes', 'status', 'type', 'reason', 'requested_by',
        'confirmed_by', 'presence_confirmation_requested',
        'presence_confirmed', 'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'presence_confirmation_requested' => 'boolean',
        'presence_confirmed' => 'boolean',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(AppointmentStatusLog::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function confirm(User $by): void
    {
        $this->statusLogs()->create([
            'from_status' => $this->status,
            'to_status' => 'confirmed',
            'changed_by' => $by->id,
        ]);

        $this->update(['status' => 'confirmed', 'confirmed_by' => $by->id]);
    }

    /** Libellé du statut en français, pour l'affichage (jamais l'enum brute). */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'rescheduled' => 'Reporté',
            'cancelled' => 'Annulé',
            'completed' => 'Terminé',
            'no_show' => 'Absence',
            default => ucfirst($this->status),
        };
    }
}
