<?php

namespace App\Livewire\PatientPortal;

use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $phone = '';
    public string $password = '';
    public string $errorMessage = '';

    public function login()
    {
        $this->errorMessage = '';

        $this->validate([
            'phone' => 'required',
            'password' => 'required',
        ], [], ['phone' => 'téléphone']);

        $patient = Patient::where('phone', $this->phone)->first();

        if (! $patient || ! $patient->password || ! \Illuminate\Support\Facades\Hash::check($this->password, $patient->password)) {
            $this->errorMessage = 'Téléphone ou mot de passe incorrect.';
            return;
        }

        Auth::guard('patient')->login($patient);

        return redirect()->route('patient.dashboard');
    }

    public function render()
    {
        return view('livewire.patient-portal.login')->layout('layouts.patient-guest');
    }
}
