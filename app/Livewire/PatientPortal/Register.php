<?php

namespace App\Livewire\PatientPortal;

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Register extends Component
{
    public string $firstName = '';
    public string $lastName = '';
    public string $phone = '';
    public string $dob = '';
    public string $sex = 'F';
    public string $password = '';
    public string $password_confirmation = '';

    // ---- Doublon détecté ----
    public bool $duplicateFound = false;
    public bool $duplicateAlreadyClaimed = false;
    public ?int $duplicatePatientId = null;
    public string $duplicateName = '';

    public function register()
    {
        $this->validate([
            'firstName' => ['required', 'min:2', 'regex:/^[\p{L}\s\-\']+$/u'],
            'lastName' => ['nullable', 'regex:/^[\p{L}\s\-\']*$/u'],
            'phone' => ['required', 'regex:/^[0-9+\-\s]+$/'],
            'dob' => 'nullable|date',
            'password' => 'required|min:6|confirmed',
        ], [
            'firstName.regex' => 'Le prénom ne doit contenir que des lettres.',
            'phone.regex' => 'Le téléphone ne doit contenir que des chiffres.',
        ], [
            'firstName' => 'prénom', 'phone' => 'téléphone',
        ]);

        $existing = Patient::where('phone', $this->phone)->first();

        if ($existing) {
            // Une patiente avec ce numéro existe déjà (créée à la réception,
            // par exemple) — on ne crée jamais de doublon. Soit elle peut
            // "réclamer" ce dossier (s'il n'a jamais eu de mot de passe),
            // soit on l'oriente vers la connexion.
            $this->duplicateFound = true;
            $this->duplicatePatientId = $existing->id;
            $this->duplicateName = $existing->first_name . ' ' . $existing->last_name;
            $this->duplicateAlreadyClaimed = ! is_null($existing->password);
            return;
        }

        $patient = Patient::create([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName ?: null,
            'phone' => $this->phone,
            'date_of_birth' => $this->dob ?: null,
            'sex' => $this->sex,
            'password' => $this->password,
            'created_via' => 'self_registration',
            // Une inscription en ligne n'est pas encore vérifiée par la
            // réception — à confirmer lors du premier passage à la clinique.
            'identity_verified' => false,
        ]);

        Auth::guard('patient')->login($patient);

        return redirect()->route('patient.dashboard');
    }

    /** La personne confirme que le dossier existant est bien le sien. */
    public function claimAccount()
    {
        $existing = Patient::findOrFail($this->duplicatePatientId);

        if ($existing->password) {
            // Sécurité : quelqu'un d'autre a déjà réclamé ce dossier entre-temps.
            $this->duplicateAlreadyClaimed = true;
            return;
        }

        $existing->update(['password' => $this->password]);

        Auth::guard('patient')->login($existing);

        return redirect()->route('patient.dashboard');
    }

    /** Ce n'est pas elle — on ne touche à rien, on l'oriente vers la réception. */
    public function notMe()
    {
        $this->duplicateFound = false;
        $this->duplicatePatientId = null;
        $this->phone = '';
    }

    public function render()
    {
        return view('livewire.patient-portal.register')->layout('layouts.patient-guest');
    }
}
