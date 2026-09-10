<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessAppointments();
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if (! $user->canAccessAppointments()) {
            return false;
        }

        if ($user->seesAllAppointments() || $user->isAdmin()) {
            return true;
        }

        // Un médecin ne voit que ses propres rendez-vous.
        if ($user->role === 'medecin') {
            return $user->doctor && $appointment->doctor_id === $user->doctor->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'reception', 'medecin'], true);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function confirm(User $user, Appointment $appointment): bool
    {
        return in_array($user->role, ['admin', 'reception'], true)
            || ($user->role === 'medecin' && $user->doctor && $appointment->doctor_id === $user->doctor->id);
    }
}
