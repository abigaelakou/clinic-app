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
        'reminder_48h_sent_at', 'reminder_24h_sent_at', 'reminder_4h_sent_at',
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

    /**
     * Nom de salle stable et non devinable pour ce RDV — sert à la fois
     * pour la patiente et le médecin, pas besoin de compte ni d'appli
     * tierce (Jitsi Meet fonctionne directement dans le navigateur).
     */
    public function jitsiRoomName(): string
    {
        return 'FAME-' . $this->id . '-' . substr(md5($this->id . $this->created_at . config('app.key')), 0, 10);
    }

    public function jitsiUrl(): string
    {
        return 'https://meet.jit.si/' . $this->jitsiRoomName();
    }

    public function isJoinableTeleconsultation(): bool
    {
        return $this->type === 'teleconsultation' && in_array($this->status, ['confirmed', 'completed'], true);
    }
}
