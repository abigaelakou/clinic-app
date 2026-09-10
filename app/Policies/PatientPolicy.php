<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    protected const NO_ACCESS = ['pharmacien', 'econome', 'cuisine'];

    public function viewAny(User $user): bool
    {
        return ! in_array($user->role, self::NO_ACCESS, true);
    }

    /**
     * Accès au dossier médical (cahier §9 + §5.3.1) :
     * - admin : informations administratives uniquement (pas le détail clinique)
     * - réception : informations administratives uniquement
     * - médecin : ses propres patientes (continuité des soins = exception à tracer)
     * - aide-soignant : lecture, pour la prise de constantes
     */
    public function view(User $user, Patient $patient): bool
    {
        if (in_array($user->role, self::NO_ACCESS, true)) {
            return false;
        }

        if (in_array($user->role, ['admin', 'reception', 'aide_soignant'], true)) {
            return true;
        }

        if ($user->role === 'medecin' && $user->doctor) {
            return $patient->appointments()->where('doctor_id', $user->doctor->id)->exists()
                || $patient->consultations()->where('doctor_id', $user->doctor->id)->exists();
        }

        return false;
    }

    /** Le contenu clinique détaillé (diagnostics, prescriptions) reste réservé au médecin traitant. */
    public function viewClinicalDetails(User $user, Patient $patient): bool
    {
        return $user->role === 'medecin' && $this->view($user, $patient);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'reception'], true);
    }

    public function update(User $user, Patient $patient): bool
    {
        return in_array($user->role, ['admin', 'reception'], true)
            || $this->view($user, $patient);
    }
}
