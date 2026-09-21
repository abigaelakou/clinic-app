<?php

namespace App\Livewire;

use App\Models\Consultation;
use App\Models\MedicalDocument;
use App\Models\Patient;
use App\Models\Vital;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Patients extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';
    public ?int $selectedPatientId = null;
    public string $activeTab = 'resume';

    // ---- Nouvelle patiente ----
    public bool $showNewPatientModal = false;

    // ---- Modale de confirmation générique (remplace confirm() du navigateur) ----
    public bool $showConfirmModal = false;
    public string $confirmMessage = '';
    public string $confirmAction = '';
    public ?int $confirmTargetId = null;

    public function askConfirm(string $action, string $message, ?int $targetId = null)
    {
        $this->confirmAction = $action;
        $this->confirmMessage = $message;
        $this->confirmTargetId = $targetId;
        $this->showConfirmModal = true;
    }

    public function closeConfirm()
    {
        $this->showConfirmModal = false;
    }

    public function runConfirmedAction()
    {
        match ($this->confirmAction) {
            'deletePatient' => $this->reallyDeletePatient(),
            'deleteConsult' => $this->reallyDeleteConsult($this->confirmTargetId),
            default => null,
        };

        $this->showConfirmModal = false;
    }
    public string $newFirstName = '';
    public string $newLastName = '';
    public string $newDob = '';
    public string $newSex = 'F';
    public string $newPhone = '';
    public string $newEmail = '';
    public string $newAddress = '';
    public string $newEmergencyName = '';
    public string $newEmergencyPhone = '';
    public string $newHistory = '';

    // ---- Import Excel ----
    public bool $showImportModal = false;
    public $importFile = null;
    public array $importSummary = [];

    // ---- Édition consultation ----
    public ?int $editingConsultId = null;

    // ---- Nouvelle consultation ----
    public bool $showConsultModal = false;
    public ?int $consultTypeId = null;
    public string $consultMotif = '';
    public string $consultDiagnostic = '';
    public string $consultPrescriptions = '';
    public string $consultExams = '';

    // ---- Nouveau type de consultation (créé à la volée) ----
    public bool $showNewTypeModal = false;
    public string $newTypeCode = '';
    public string $newTypeLabel = '';
    public string $newTypeColor = '#C0410C';

    // ---- Nouveau document ----
    public bool $showDocModal = false;
    public string $docTitle = '';
    public string $docType = 'ordonnance';
    public bool $docShared = false;
    public $docFile = [];

    // ---- Nouvelles constantes ----
    public bool $showVitalsModal = false;
    public $vitalsWeight = null;
    public $vitalsHeight = null;
    public string $vitalsBp = '';
    public $vitalsTemp = null;
    public $vitalsHeartRate = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        if (! Auth::user()->can('viewAny', Patient::class)) {
            abort(403, "Ton rôle n'a pas accès aux dossiers patients.");
        }
    }

    // ---------- Nouvelle patiente ----------

    public function openNewPatient()
    {
        if (! Auth::user()->can('create', Patient::class)) {
            abort(403);
        }

        $this->newFirstName = '';
        $this->newLastName = '';
        $this->newDob = '';
        $this->newSex = 'F';
        $this->newPhone = '';
        $this->newEmail = '';
        $this->newAddress = '';
        $this->newEmergencyName = '';
        $this->newEmergencyPhone = '';
        $this->newHistory = '';
        $this->resetErrorBag();
        $this->showNewPatientModal = true;
    }

    public function closeNewPatient()
    {
        $this->showNewPatientModal = false;
    }

    public function saveNewPatient()
    {
        if (! Auth::user()->can('create', Patient::class)) {
            abort(403);
        }

        $this->validate([
            'newFirstName' => ['required', 'min:2', 'regex:/^[\p{L}\s\-\']+$/u'],
            'newLastName' => ['nullable', 'regex:/^[\p{L}\s\-\']*$/u'],
            'newPhone' => ['required', 'unique:patients,phone', 'regex:/^[0-9+\-\s]+$/'],
            'newEmergencyName' => ['nullable', 'regex:/^[\p{L}\s\-\']*$/u'],
            'newEmergencyPhone' => ['nullable', 'regex:/^[0-9+\-\s]*$/'],
            'newDob' => 'nullable|date',
            'newEmail' => 'nullable|email',
        ], [
            'newFirstName.regex' => 'Le prénom ne doit contenir que des lettres.',
            'newLastName.regex' => 'Le nom ne doit contenir que des lettres.',
            'newPhone.regex' => 'Le téléphone ne doit contenir que des chiffres.',
            'newEmergencyName.regex' => 'Ce champ ne doit contenir que des lettres.',
            'newEmergencyPhone.regex' => 'Ce champ ne doit contenir que des chiffres.',
        ], [
            'newFirstName' => 'prénom', 'newPhone' => 'téléphone',
        ]);

        $patient = Patient::create([
            'first_name' => $this->newFirstName,
            'last_name' => $this->newLastName,
            'date_of_birth' => $this->newDob ?: null,
            'sex' => $this->newSex,
            'phone' => $this->newPhone,
            'email' => $this->newEmail ?: null,
            'address' => $this->newAddress ?: null,
            'emergency_contact_name' => $this->newEmergencyName ?: null,
            'emergency_contact_phone' => $this->newEmergencyPhone ?: null,
            'declared_history' => $this->newHistory ?: null,
            'created_via' => 'reception',
            'created_by' => Auth::id(),
            'identity_verified' => true,
        ]);

        $this->showNewPatientModal = false;
        $this->selectedPatientId = $patient->id;
        $this->activeTab = 'resume';
        $this->dispatch('toast', message: $patient->first_name . ' ajoutée aux dossiers patients.');
    }

    // ---------- Modifier patiente ----------

    public bool $showEditPatientModal = false;
    public string $editFirstName = '';
    public string $editLastName = '';
    public string $editDob = '';
    public string $editSex = 'F';
    public string $editPhone = '';
    public string $editEmail = '';
    public string $editAddress = '';
    public string $editEmergencyName = '';
    public string $editEmergencyPhone = '';
    public string $editHistory = '';

    public function openEditPatient()
    {
        $patient = Patient::findOrFail($this->selectedPatientId);

        if (! in_array(Auth::user()->role, ['admin', 'reception'], true)) {
            abort(403);
        }

        $this->editFirstName = $patient->first_name;
        $this->editLastName = $patient->last_name ?? '';
        $this->editDob = $patient->date_of_birth?->toDateString() ?? '';
        $this->editSex = $patient->sex;
        $this->editPhone = $patient->phone;
        $this->editEmail = $patient->email ?? '';
        $this->editAddress = $patient->address ?? '';
        $this->editEmergencyName = $patient->emergency_contact_name ?? '';
        $this->editEmergencyPhone = $patient->emergency_contact_phone ?? '';
        $this->editHistory = $patient->declared_history ?? '';
        $this->resetErrorBag();
        $this->showEditPatientModal = true;
    }

    public function closeEditPatient()
    {
        $this->showEditPatientModal = false;
    }

    public function saveEditPatient()
    {
        if (! in_array(Auth::user()->role, ['admin', 'reception'], true)) {
            abort(403);
        }

        $patient = Patient::findOrFail($this->selectedPatientId);

        $this->validate([
            'editFirstName' => ['required', 'min:2', 'regex:/^[\p{L}\s\-\']+$/u'],
            'editLastName' => ['nullable', 'regex:/^[\p{L}\s\-\']*$/u'],
            'editPhone' => ['required', 'unique:patients,phone,' . $patient->id, 'regex:/^[0-9+\-\s]+$/'],
            'editEmergencyName' => ['nullable', 'regex:/^[\p{L}\s\-\']*$/u'],
            'editEmergencyPhone' => ['nullable', 'regex:/^[0-9+\-\s]*$/'],
            'editDob' => 'nullable|date',
            'editEmail' => 'nullable|email',
        ], [
            'editFirstName.regex' => 'Le prénom ne doit contenir que des lettres.',
            'editLastName.regex' => 'Le nom ne doit contenir que des lettres.',
            'editPhone.regex' => 'Le téléphone ne doit contenir que des chiffres.',
            'editEmergencyName.regex' => 'Ce champ ne doit contenir que des lettres.',
            'editEmergencyPhone.regex' => 'Ce champ ne doit contenir que des chiffres.',
        ], ['editFirstName' => 'prénom', 'editPhone' => 'téléphone']);

        $patient->update([
            'first_name' => $this->editFirstName,
            'last_name' => $this->editLastName ?: null,
            'date_of_birth' => $this->editDob ?: null,
            'sex' => $this->editSex,
            'phone' => $this->editPhone,
            'email' => $this->editEmail ?: null,
            'address' => $this->editAddress ?: null,
            'emergency_contact_name' => $this->editEmergencyName ?: null,
            'emergency_contact_phone' => $this->editEmergencyPhone ?: null,
            'declared_history' => $this->editHistory ?: null,
        ]);

        $this->showEditPatientModal = false;
        $this->dispatch('toast', message: 'Fiche patiente mise à jour.');
    }

    // ---------- Retirer patiente ----------

    /**
     * Retire la patiente de la liste sans jamais perdre ses données : on
     * utilise la suppression douce déjà en place sur le modèle (SoftDeletes),
     * donc consultations, documents et constantes restent en base, juste
     * plus visibles dans l'appli. Réservé à l'admin, vu la sensibilité.
     */
    protected function reallyDeletePatient()
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        $patient = Patient::findOrFail($this->selectedPatientId);
        $name = $patient->first_name;
        $patient->delete();

        $this->selectedPatientId = null;
        $this->dispatch('toast', message: $name . ' retirée des dossiers patients.');
    }

    // ---------- Import Excel ----------

    public function openImport()
    {
        if (! Auth::user()->can('create', Patient::class)) {
            abort(403);
        }

        $this->importFile = null;
        $this->importSummary = [];
        $this->resetErrorBag();
        $this->showImportModal = true;
    }

    public function closeImport()
    {
        $this->showImportModal = false;
    }

    public function saveImport()
    {
        if (! Auth::user()->can('create', Patient::class)) {
            abort(403);
        }

        $this->validate(['importFile' => 'required|file|mimes:xlsx,xls'], [], ['importFile' => 'fichier']);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($this->importFile->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        $created = 0;
        $skipped = 0;
        $errors = [];

        // Ligne 0 = en-têtes, on les ignore. Ordre attendu (voir le modèle) :
        // Prénom | Nom | Téléphone | Date de naissance | Sexe | Email | Adresse | Contact urgence nom | Contact urgence téléphone | Antécédents
        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // en-têtes
            if (empty($row[0]) && empty($row[2])) continue; // ligne vide

            $firstName = trim((string) ($row[0] ?? ''));
            $phone = trim((string) ($row[2] ?? ''));

            if ($firstName === '' || $phone === '') {
                $errors[] = 'Ligne ' . ($i + 1) . ' : prénom ou téléphone manquant, ignorée.';
                $skipped++;
                continue;
            }

            if (Patient::where('phone', $phone)->exists()) {
                $errors[] = 'Ligne ' . ($i + 1) . " : téléphone déjà existant ($phone), ignorée.";
                $skipped++;
                continue;
            }

            $dob = null;
            if (! empty($row[3])) {
                try {
                    $dob = is_numeric($row[3])
                        ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[3])
                        : \Carbon\Carbon::parse($row[3]);
                } catch (\Throwable $e) {
                    $dob = null;
                }
            }

            Patient::create([
                'first_name' => $firstName,
                'last_name' => trim((string) ($row[1] ?? '')) ?: null,
                'phone' => $phone,
                'date_of_birth' => $dob,
                'sex' => in_array(strtoupper((string) ($row[4] ?? 'F')), ['F', 'M'], true) ? strtoupper($row[4]) : 'F',
                'email' => trim((string) ($row[5] ?? '')) ?: null,
                'address' => trim((string) ($row[6] ?? '')) ?: null,
                'emergency_contact_name' => trim((string) ($row[7] ?? '')) ?: null,
                'emergency_contact_phone' => trim((string) ($row[8] ?? '')) ?: null,
                'declared_history' => trim((string) ($row[9] ?? '')) ?: null,
                'created_via' => 'reception',
                'created_by' => Auth::id(),
                'identity_verified' => true,
            ]);

            $created++;
        }

        $this->importSummary = [
            'created' => $created,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 10),
        ];

        if ($created > 0) {
            $this->dispatch('toast', message: $created . ' patiente(s) importée(s).');
        }
    }

    protected function visiblePatientsQuery()
    {
        $user = Auth::user();
        $query = Patient::query();

        if ($user->role === 'medecin' && $user->doctor) {
            $doctorId = $user->doctor->id;
            $query->where(function ($q) use ($doctorId) {
                $q->whereHas('appointments', fn ($a) => $a->where('doctor_id', $doctorId))
                  ->orWhereHas('consultations', fn ($c) => $c->where('doctor_id', $doctorId));
            });
        } elseif ($user->role === 'medecin' && ! $user->doctor) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function selectPatient(int $id)
    {
        $patient = Patient::findOrFail($id);

        if (! Auth::user()->can('view', $patient)) {
            abort(403);
        }

        $patient->logAccess(Auth::user(), 'view');

        $this->selectedPatientId = $id;
        $this->activeTab = 'resume';
        $this->resetPage('consultPage');
        $this->resetPage('docPage');
        $this->resetPage('vitalsPage');
        $this->resetPage('journalPage');
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    // ---------- Nouvelle consultation ----------

    public function openConsult()
    {
        if (Auth::user()->role !== 'medecin') {
            abort(403);
        }

        $this->editingConsultId = null;
        $this->consultTypeId = null;
        $this->consultMotif = '';
        $this->consultDiagnostic = '';
        $this->consultPrescriptions = '';
        $this->consultExams = '';
        $this->resetErrorBag();
        $this->showConsultModal = true;
    }

    public function openEditConsult(int $id)
    {
        $consult = Consultation::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'medecin' || ! $user->doctor || $consult->doctor_id !== $user->doctor->id) {
            abort(403, 'Tu ne peux modifier que tes propres consultations.');
        }

        $this->editingConsultId = $consult->id;
        $this->consultTypeId = $consult->consultation_type_id;
        $this->consultMotif = $consult->motif;
        $this->consultDiagnostic = $consult->diagnostic ?? '';
        $this->consultPrescriptions = $consult->prescriptions ?? '';
        $this->consultExams = $consult->exams_requested ?? '';
        $this->resetErrorBag();
        $this->showConsultModal = true;
    }

    protected function reallyDeleteConsult(?int $id)
    {
        $consult = Consultation::findOrFail($id);
        $user = Auth::user();

        if ($user->role !== 'medecin' || ! $user->doctor || $consult->doctor_id !== $user->doctor->id) {
            abort(403, 'Tu ne peux supprimer que tes propres consultations.');
        }

        $consult->delete();
        $this->dispatch('toast', message: 'Consultation supprimée.');
    }

    public function closeConsult()
    {
        $this->showConsultModal = false;
    }

    // ---------- Nouveau type de consultation ----------

    public function openNewType()
    {
        if (Auth::user()->role !== 'medecin' && ! Auth::user()->isAdmin()) {
            abort(403);
        }

        $this->newTypeCode = '';
        $this->newTypeLabel = '';
        $this->newTypeColor = '#C0410C';
        $this->resetErrorBag();
        $this->showNewTypeModal = true;
    }

    public function closeNewType()
    {
        $this->showNewTypeModal = false;
    }

    public function saveNewType()
    {
        $this->validate([
            'newTypeCode' => 'required|max:10|unique:consultation_types,code',
            'newTypeLabel' => 'required|min:2',
        ], [], ['newTypeCode' => 'code', 'newTypeLabel' => 'libellé']);

        $type = \App\Models\ConsultationType::create([
            'code' => strtoupper($this->newTypeCode),
            'label' => $this->newTypeLabel,
            'color' => $this->newTypeColor,
        ]);

        $this->consultTypeId = $type->id;
        $this->showNewTypeModal = false;
        $this->dispatch('toast', message: 'Type "' . $type->label . '" créé et sélectionné.');
    }

    public function saveConsult()
    {
        $this->validate(['consultMotif' => 'required|min:3'], [], ['consultMotif' => 'motif']);

        $user = Auth::user();
        $patient = Patient::findOrFail($this->selectedPatientId);

        if (! Auth::user()->can('view', $patient) || $user->role !== 'medecin') {
            abort(403);
        }

        $data = [
            'consultation_type_id' => $this->consultTypeId ?: null,
            'motif' => $this->consultMotif,
            'diagnostic' => $this->consultDiagnostic ?: null,
            'prescriptions' => $this->consultPrescriptions ?: null,
            'exams_requested' => $this->consultExams ?: null,
        ];

        if ($this->editingConsultId) {
            $consult = Consultation::findOrFail($this->editingConsultId);
            if ($consult->doctor_id !== $user->doctor->id) {
                abort(403);
            }
            $consult->update($data);
            $message = 'Consultation modifiée.';
        } else {
            Consultation::create(array_merge($data, [
                'patient_id' => $patient->id,
                'doctor_id' => $user->doctor->id,
                'consulted_at' => now(),
            ]));
            $message = 'Consultation ajoutée au dossier.';
        }

        $this->showConsultModal = false;
        $this->resetPage('consultPage');
        $this->dispatch('toast', message: $message);
    }

    // ---------- Nouveau document ----------

    public function openDoc()
    {
        if (! in_array(Auth::user()->role, ['medecin', 'admin', 'reception'], true)) {
            abort(403);
        }

        $this->docTitle = '';
        $this->docType = 'ordonnance';
        $this->docShared = false;
        $this->docFile = [];
        $this->resetErrorBag();
        $this->showDocModal = true;
    }

    public function closeDoc()
    {
        $this->showDocModal = false;
    }

    public function saveDoc()
    {
        $this->validate([
            'docFile' => 'required|array|min:1',
            'docFile.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:25600',
        ], [
            'docFile.required' => 'Choisis au moins un fichier.',
            'docFile.*.mimes' => 'Formats acceptés : PDF, image (JPG/PNG), ou document Word (DOC/DOCX).',
            'docFile.*.max' => 'Chaque fichier doit faire moins de 25 Mo.',
        ]);

        $patient = Patient::findOrFail($this->selectedPatientId);

        if (! Auth::user()->can('view', $patient)) {
            abort(403);
        }

        $files = is_array($this->docFile) ? $this->docFile : [$this->docFile];
        $count = count($files);

        foreach ($files as $i => $file) {
            $path = $file->store('documents', 'public');

            // Titre : celui saisi (avec un numéro si plusieurs fichiers), sinon le nom du fichier.
            if (trim($this->docTitle) !== '') {
                $title = $count > 1 ? $this->docTitle . ' (' . ($i + 1) . ')' : $this->docTitle;
            } else {
                $title = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            }

            MedicalDocument::create([
                'patient_id' => $patient->id,
                'uploaded_by' => Auth::id(),
                'title' => $title,
                'type' => $this->docType,
                'file_path' => $path,
                'shared_with_patient' => $this->docShared,
            ]);
        }

        $this->showDocModal = false;
        $this->resetPage('docPage');
        $this->dispatch('toast', message: $count > 1 ? $count . ' documents ajoutés au dossier.' : 'Document ajouté au dossier.');
    }

    // ---------- Nouvelles constantes ----------

    public function openVitals()
    {
        if (! in_array(Auth::user()->role, ['aide_soignant', 'medecin', 'admin'], true)) {
            abort(403);
        }

        $this->vitalsWeight = null;
        $this->vitalsHeight = null;
        $this->vitalsBp = '';
        $this->vitalsTemp = null;
        $this->vitalsHeartRate = null;
        $this->resetErrorBag();
        $this->showVitalsModal = true;
    }

    public function closeVitals()
    {
        $this->showVitalsModal = false;
    }

    public function saveVitals()
    {
        $patient = Patient::findOrFail($this->selectedPatientId);

        if (! Auth::user()->can('view', $patient)) {
            abort(403);
        }

        Vital::create([
            'patient_id' => $patient->id,
            'recorded_by' => Auth::id(),
            'weight_kg' => $this->vitalsWeight ?: null,
            'height_cm' => $this->vitalsHeight ?: null,
            'blood_pressure' => $this->vitalsBp ?: null,
            'temperature_c' => $this->vitalsTemp ?: null,
            'heart_rate' => $this->vitalsHeartRate ?: null,
        ]);

        $this->showVitalsModal = false;
        $this->resetPage('vitalsPage');
        $this->dispatch('toast', message: 'Constantes enregistrées.');
    }

    public function render()
    {
        $user = Auth::user();

        $patients = $this->visiblePatientsQuery()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
            ))
            ->orderBy('first_name')
            ->simplePaginate(15);

        $selectedPatient = null;
        $consultations = collect();
        $documents = collect();
        $vitals = collect();
        $accessLogs = collect();
        $canViewClinical = false;

        if ($this->selectedPatientId) {
            $selectedPatient = Patient::find($this->selectedPatientId);

            if ($selectedPatient && Auth::user()->can('view', $selectedPatient)) {
                $canViewClinical = Auth::user()->can('viewClinicalDetails', $selectedPatient);
                $consultations = $selectedPatient->consultations()->with(['doctor.user', 'type'])->latest('consulted_at')->simplePaginate(8, ['*'], 'consultPage');
                $documents = $selectedPatient->documents()->with('uploadedBy')->latest()->simplePaginate(8, ['*'], 'docPage');
                $vitals = $selectedPatient->vitals()->with('recordedBy')->latest('recorded_at')->simplePaginate(8, ['*'], 'vitalsPage');

                if ($user->isAdmin()) {
                    $accessLogs = $selectedPatient->accessLogs()->with('user')->latest('accessed_at')->simplePaginate(15, ['*'], 'journalPage');
                }
            } else {
                $selectedPatient = null;
            }
        }

        return view('livewire.patients', [
            'patients' => $patients,
            'selectedPatient' => $selectedPatient,
            'consultations' => $consultations,
            'documents' => $documents,
            'vitalsList' => $vitals,
            'accessLogs' => $accessLogs,
            'canViewClinical' => $canViewClinical,
            'canViewAuditLog' => $user->isAdmin(),
            'canAddConsult' => $user->role === 'medecin',
            'consultationTypes' => \App\Models\ConsultationType::where('is_active', true)->orderBy('code')->get(),
            'canAddDoc' => in_array($user->role, ['medecin', 'admin', 'reception'], true),
            'canAddVitals' => in_array($user->role, ['aide_soignant', 'medecin', 'admin'], true),
            'canCreatePatient' => $user->can('create', Patient::class),
            'canManageTypes' => $user->role === 'medecin' || $user->isAdmin(),
            'canEditPatient' => in_array($user->role, ['admin', 'reception'], true),
            'canDeletePatient' => $user->isAdmin(),
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
