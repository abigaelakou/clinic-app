<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Modale "Nouveau rendez-vous", pensée pour être incluse depuis n'importe
 * quelle page (<livewire:new-appointment-modal />) — dashboard, page RDV,
 * dossier patient plus tard, etc. — sans dupliquer le formulaire.
 *
 * Pour l'ouvrir depuis un bouton : wire:click="$dispatch('open-new-appointment-modal')"
 * La page qui l'inclut peut écouter #[On('appointment-created')] pour se
 * rafraîchir automatiquement après création.
 */
class NewAppointmentModal extends Component
{
    public bool $show = false;

    public ?int $newPatientId = null;
    public string $selectedPatientName = '';
    public string $patientSearch = '';

    public ?int $newDoctorId = null;
    public string $selectedDoctorName = '';
    public string $doctorSearch = '';

    public string $newDate = '';
    public string $newTime = '09:00';
    public string $newType = 'in_person';
    public string $newReason = '';

    #[On('open-new-appointment-modal')]
    public function open()
    {
        if (! Auth::user()->can('create', Appointment::class)) {
            abort(403);
        }

        $this->reset([
            'newPatientId', 'selectedPatientName', 'patientSearch',
            'newDoctorId', 'selectedDoctorName', 'doctorSearch',
            'newType', 'newReason',
        ]);
        $this->newDate = today()->toDateString();
        $this->newTime = now()->format('H:i');

        if (Auth::user()->role === 'medecin' && Auth::user()->doctor) {
            $doctor = Auth::user()->doctor()->with('user')->first();
            $this->newDoctorId = $doctor->id;
            $this->selectedDoctorName = $doctor->user->name;
        }

        $this->resetErrorBag();
        $this->show = true;
    }

    public function close()
    {
        $this->show = false;
    }

    public function selectPatient(int $patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $this->newPatientId = $patient->id;
        $this->selectedPatientName = $patient->first_name . ' ' . $patient->last_name;
        $this->patientSearch = '';
    }

    public function selectDoctor(int $doctorId)
    {
        $doctor = Doctor::with('user')->findOrFail($doctorId);
        $this->newDoctorId = $doctor->id;
        $this->selectedDoctorName = $doctor->user->name;
        $this->doctorSearch = '';
    }

    public function save()
    {
        $this->validate([
            'newPatientId' => 'required|exists:patients,id',
            'newDoctorId' => 'required|exists:doctors,id',
            'newDate' => 'required|date',
            'newTime' => 'required',
            'newReason' => 'required|min:3',
        ], [], [
            'newPatientId' => 'patiente',
            'newDoctorId' => 'médecin',
            'newDate' => 'date',
            'newTime' => 'heure',
            'newReason' => 'motif',
        ]);

        $doctor = Doctor::findOrFail($this->newDoctorId);

        if (Auth::user()->role === 'medecin' && Auth::user()->doctor && $doctor->id !== Auth::user()->doctor->id) {
            abort(403);
        }

        Appointment::create([
            'patient_id' => $this->newPatientId,
            'doctor_id' => $doctor->id,
            'specialty_id' => $doctor->specialty_id,
            'scheduled_at' => $this->newDate . ' ' . $this->newTime,
            'status' => 'confirmed',
            'type' => $this->newType,
            'reason' => $this->newReason,
            'requested_by' => in_array(Auth::user()->role, ['admin', 'reception'], true) ? 'reception' : 'medecin',
        ]);

        $this->show = false;
        $this->dispatch('toast', message: 'Rendez-vous créé.');

        // Les pages qui incluent ce composant peuvent écouter cet événement
        // pour rafraîchir leurs propres listes (agenda, compteurs...).
        $this->dispatch('appointment-created');
    }

    public function render()
    {
        $user = Auth::user();

        $patientsAll = Patient::orderBy('first_name')->limit(200)->get();
        $filteredPatients = $this->patientSearch === ''
            ? $patientsAll->take(30)
            : $patientsAll->filter(fn ($p) => str_contains(
                strtolower($p->first_name . ' ' . $p->last_name), strtolower($this->patientSearch)
            ))->take(30);

        $filteredDoctors = collect();
        if ($user->role !== 'medecin') {
            $doctorsAll = Doctor::with('user')->get();
            $filteredDoctors = $this->doctorSearch === ''
                ? $doctorsAll
                : $doctorsAll->filter(fn ($d) => str_contains(strtolower($d->user->name), strtolower($this->doctorSearch)));
        }

        return view('livewire.new-appointment-modal', [
            'filteredPatients' => $filteredPatients,
            'filteredDoctors' => $filteredDoctors,
        ]);
    }
}
