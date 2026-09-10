<?php

namespace App\Livewire;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Rdv extends Component
{
    public function confirm(int $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if (! Auth::user()->can('confirm', $appointment)) {
            abort(403);
        }

        $appointment->confirm(Auth::user());

        session()->flash('flash', 'Rendez-vous confirmé.');
    }

    public function render()
    {
        $user = Auth::user();

        if (! $user->canAccessAppointments()) {
            abort(403, "Ton rôle n'a pas accès au module Rendez-vous.");
        }

        $todayQuery = Appointment::today()->orderBy('scheduled_at')->with(['patient', 'doctor.user']);
        $pendingQuery = Appointment::pending()->with(['patient', 'doctor.user'])->orderBy('scheduled_at');

        if ($user->role === 'medecin' && $user->doctor && ! $user->seesAllAppointments()) {
            $todayQuery->where('doctor_id', $user->doctor->id);
            $pendingQuery->where('doctor_id', $user->doctor->id);
        } elseif ($user->role === 'medecin' && ! $user->doctor) {
            $todayQuery->whereRaw('1 = 0');
            $pendingQuery->whereRaw('1 = 0');
        }

        $today = $todayQuery->get();
        $pending = $pendingQuery->limit(5)->get();

        // Occupation du jour par médecin (nombre de RDV, faute de données de disponibilité pour l'instant)
        $byDoctor = $today->groupBy(fn ($a) => $a->doctor->user->name ?? 'Médecin');

        return view('livewire.rdv', [
            'today' => $today,
            'pending' => $pending,
            'byDoctor' => $byDoctor,
            'canConfirm' => in_array($user->role, ['admin', 'reception', 'medecin'], true),
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
