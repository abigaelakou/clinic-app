<?php

namespace App\Livewire\PatientPortal;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showBookModal = false;
    public ?int $bookDoctorId = null;
    public string $bookDoctorName = '';
    public string $doctorSearch = '';
    public string $bookDate = '';
    public string $bookTime = '';
    public string $bookReason = '';

    public function mount()
    {
        if (! Auth::guard('patient')->check()) {
            return redirect()->route('patient.login');
        }
    }

    public $idDocument = null;

    public function uploadIdDocument()
    {
        $this->validate([
            'idDocument' => 'required|file|mimes:pdf,jpg,jpeg,png|max:8192',
        ], [], ['idDocument' => 'pièce d\'identité']);

        $patient = Auth::guard('patient')->user();
        $path = $this->idDocument->store('id-documents', 'public');

        $patient->update(['id_document_path' => $path]);

        $this->idDocument = null;
        $this->dispatch('toast', message: 'Pièce envoyée — elle sera vérifiée à ton prochain passage à la clinique.');
    }

    // ---------- Nouvelle date proposée par la clinique ----------

    public bool $showAltModal = false;
    public ?int $altAppointmentId = null;
    public string $altDate = '';
    public string $altTime = '';

    public function acceptReschedule(int $id)
    {
        $patient = Auth::guard('patient')->user();
        $appointment = $patient->appointments()->where('id', $id)->where('status', 'rescheduled')->firstOrFail();

        $appointment->statusLogs()->create([
            'from_status' => 'rescheduled',
            'to_status' => 'confirmed',
            'reason' => 'Nouvelle date acceptée par la patiente',
        ]);
        $appointment->update(['status' => 'confirmed']);

        $this->dispatch('toast', message: 'Rendez-vous confirmé.');
    }

    public function openProposeAlt(int $id)
    {
        $this->altAppointmentId = $id;
        $this->altDate = '';
        $this->altTime = '';
        $this->resetErrorBag();
        $this->showAltModal = true;
    }

    public function closeProposeAlt()
    {
        $this->showAltModal = false;
    }

    public function saveProposeAlt()
    {
        $this->validate([
            'altDate' => 'required|date|after_or_equal:today',
            'altTime' => 'required',
        ], [], ['altDate' => 'date', 'altTime' => 'heure']);

        $patient = Auth::guard('patient')->user();
        $appointment = $patient->appointments()->where('id', $this->altAppointmentId)->where('status', 'rescheduled')->firstOrFail();

        $appointment->statusLogs()->create([
            'from_status' => 'rescheduled',
            'to_status' => 'pending',
            'reason' => 'Autre créneau proposé par la patiente',
        ]);

        $appointment->update([
            'scheduled_at' => $this->altDate . ' ' . $this->altTime,
            'status' => 'pending',
        ]);

        $this->showAltModal = false;
        $this->dispatch('toast', message: 'Ta proposition a été envoyée à la clinique.');
    }

    public function openBook()
    {
        $patient = Auth::guard('patient')->user();

        // Présélectionne le médecin qui suit déjà la patiente (dernier RDV
        // ou dernière consultation), tout en lui laissant la main pour
        // chercher un autre médecin si elle veut consulter quelqu'un d'autre.
        $usualDoctor = $patient->appointments()->latest('scheduled_at')->with('doctor.user')->first()?->doctor
            ?? $patient->consultations()->latest('consulted_at')->with('doctor.user')->first()?->doctor;

        $this->bookDoctorId = $usualDoctor?->id;
        $this->bookDoctorName = $usualDoctor ? ($usualDoctor->user->name ?? '') : '';
        $this->doctorSearch = '';
        $this->bookDate = '';
        $this->bookTime = '';
        $this->bookReason = '';
        $this->resetErrorBag();
        $this->showBookModal = true;
    }

    public function selectDoctor(int $doctorId)
    {
        $doctor = Doctor::with('user')->findOrFail($doctorId);
        $this->bookDoctorId = $doctor->id;
        $this->bookDoctorName = $doctor->user->name ?? '';
        $this->doctorSearch = '';
    }

    public function closeBook()
    {
        $this->showBookModal = false;
    }

    public function saveBook()
    {
        $this->validate([
            'bookDoctorId' => 'required|exists:doctors,id',
            'bookDate' => 'required|date|after_or_equal:today',
            'bookTime' => 'required',
            'bookReason' => 'required|min:3',
        ], [], [
            'bookDoctorId' => 'médecin', 'bookDate' => 'date', 'bookTime' => 'heure', 'bookReason' => 'motif',
        ]);

        $patient = Auth::guard('patient')->user();
        $doctor = Doctor::findOrFail($this->bookDoctorId);

        $unavailable = DoctorAvailability::where('doctor_id', $doctor->id)
            ->where('specific_date', $this->bookDate)
            ->exists();

        if ($unavailable) {
            $this->addError('bookDate', 'Ce médecin a indiqué être indisponible ce jour-là — choisis une autre date.');
            return;
        }

        Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $this->bookDoctorId,
            'specialty_id' => $doctor->specialty_id,
            'scheduled_at' => $this->bookDate . ' ' . $this->bookTime,
            'reason' => $this->bookReason,
            'status' => 'pending',
            'type' => 'in_person',
            'requested_by' => 'patient',
        ]);

        $this->showBookModal = false;
        $this->dispatch('toast', message: 'Demande envoyée — la clinique va la confirmer ou te proposer un autre créneau.');
    }

    public function logout()
    {
        Auth::guard('patient')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('patient.login');
    }

    public function render()
    {
        $patient = Auth::guard('patient')->user();

        $selectedDateUnavailable = false;
        if ($this->bookDoctorId && $this->bookDate) {
            $selectedDateUnavailable = DoctorAvailability::where('doctor_id', $this->bookDoctorId)
                ->where('specific_date', $this->bookDate)
                ->exists();
        }

        $upcoming = $patient->appointments()
            ->where('scheduled_at', '>=', now())
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('scheduled_at')
            ->with('doctor.user')
            ->simplePaginate(5, ['*'], 'apptPage');

        $documents = $patient->sharedDocuments()->latest()->simplePaginate(5, ['*'], 'docPage');

        $consultations = $patient->consultations()->with('doctor.user')->latest('consulted_at')->simplePaginate(5, ['*'], 'consultPage');
        $vitals = $patient->vitals()->latest('recorded_at')->simplePaginate(5, ['*'], 'vitalsPage');

        $filteredDoctors = Doctor::with('user', 'specialty')
            ->when($this->doctorSearch !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->doctorSearch}%")))
            ->limit(8)
            ->get();

        return view('livewire.patient-portal.dashboard', [
            'patient' => $patient,
            'upcoming' => $upcoming,
            'documents' => $documents,
            'consultations' => $consultations,
            'vitals' => $vitals,
            'filteredDoctors' => $filteredDoctors,
            'selectedDateUnavailable' => $selectedDateUnavailable,
        ])->layout('layouts.patient-app');
    }
}
