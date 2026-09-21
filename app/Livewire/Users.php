<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search = '';

    public const ROLES = [
        'admin' => 'Administrateur',
        'medecin' => 'Médecin',
        'pharmacien' => 'Pharmacien',
        'econome' => 'Économat',
        'cuisine' => 'Personnel de cuisine',
        'aide_soignant' => 'Aide-soignant',
        'reception' => 'Réception',
    ];

    // ---- Nouveau compte ----
    public bool $showNewModal = false;
    public string $newName = '';
    public string $newEmail = '';
    public string $newPhone = '';
    public string $newRole = 'reception';
    public string $newPassword = '';
    public ?int $newSpecialtyId = null;
    public string $newTitle = 'Dr';
    public bool $newTele = false;
    public string $newSpecialtyName = '';

    // ---- Modifier compte ----
    public bool $showEditModal = false;
    public ?int $editUserId = null;
    public string $editName = '';
    public string $editPhone = '';
    public string $editRole = '';
    public string $editNewPassword = '';
    public ?int $editSpecialtyId = null;
    public string $editSpecialtyName = '';
    public string $editTitle = 'Dr';
    public bool $editTele = false;

    // ---- Confirmation générique ----
    public bool $showConfirmModal = false;
    public string $confirmMessage = '';
    public string $confirmAction = '';
    public ?int $confirmTargetId = null;

    public function mount()
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, "Seul un administrateur peut gérer les comptes.");
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    // ---------- Nouveau compte ----------

    public function openNew()
    {
        $this->newName = '';
        $this->newEmail = '';
        $this->newPhone = '';
        $this->newRole = 'reception';
        $this->newPassword = '';
        $this->newSpecialtyId = null;
        $this->newTitle = 'Dr';
        $this->newTele = false;
        $this->newSpecialtyName = '';
        $this->resetErrorBag();
        $this->showNewModal = true;
    }

    public function closeNew()
    {
        $this->showNewModal = false;
    }

    public function saveNew()
    {
        $this->validate([
            'newName' => ['required', 'min:2', 'regex:/^[\p{L}\s\-\']+$/u'],
            'newEmail' => 'required|email|unique:users,email',
            'newPhone' => ['nullable', 'regex:/^[0-9+\-\s]*$/'],
            'newRole' => 'required|in:' . implode(',', array_keys(self::ROLES)),
            'newPassword' => 'required|min:6',
        ], [
            'newName.regex' => 'Le nom ne doit contenir que des lettres.',
            'newPhone.regex' => 'Le téléphone ne doit contenir que des chiffres.',
        ], [
            'newName' => 'nom', 'newEmail' => 'e-mail', 'newPassword' => 'mot de passe',
        ]);

        if ($this->newRole === 'medecin' && ! $this->newSpecialtyId && trim($this->newSpecialtyName) === '') {
            $this->addError('newSpecialtyId', 'Choisis une spécialité existante ou saisis-en une nouvelle.');
            return;
        }

        $user = User::create([
            'name' => $this->newName,
            'email' => $this->newEmail,
            'phone' => $this->newPhone ?: null,
            'role' => $this->newRole,
            'password' => $this->newPassword,
            'is_active' => true,
        ]);

        if ($this->newRole === 'medecin') {
            $specialtyId = $this->newSpecialtyId;
            if (! $specialtyId) {
                $specialtyId = Specialty::firstOrCreate(['name' => trim($this->newSpecialtyName)])->id;
            }

            Doctor::create([
                'user_id' => $user->id,
                'specialty_id' => $specialtyId,
                'title' => $this->newTitle,
                'teleconsultation_enabled' => $this->newTele,
            ]);
        }

        $this->showNewModal = false;
        $this->dispatch('toast', message: 'Compte créé pour ' . $user->name . '.');
    }

    // ---------- Modifier compte ----------

    public function openEdit(int $id)
    {
        $user = User::findOrFail($id);
        $this->editUserId = $user->id;
        $this->editName = $user->name;
        $this->editPhone = $user->phone ?? '';
        $this->editRole = $user->role;
        $this->editNewPassword = '';
        $this->editSpecialtyId = $user->doctor->specialty_id ?? null;
        $this->editSpecialtyName = '';
        $this->editTitle = $user->doctor->title ?? 'Dr';
        $this->editTele = $user->doctor->teleconsultation_enabled ?? false;
        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function closeEdit()
    {
        $this->showEditModal = false;
    }

    public function saveEdit()
    {
        $user = User::findOrFail($this->editUserId);
        $becomingDoctor = $this->editRole === 'medecin' && ! $user->doctor;

        $this->validate([
            'editName' => ['required', 'min:2', 'regex:/^[\p{L}\s\-\']+$/u'],
            'editPhone' => ['nullable', 'regex:/^[0-9+\-\s]*$/'],
            'editRole' => 'required|in:' . implode(',', array_keys(self::ROLES)),
            'editNewPassword' => 'nullable|min:6',
        ], [
            'editName.regex' => 'Le nom ne doit contenir que des lettres.',
            'editPhone.regex' => 'Le téléphone ne doit contenir que des chiffres.',
        ], ['editName' => 'nom']);

        if ($becomingDoctor && ! $this->editSpecialtyId && trim($this->editSpecialtyName) === '') {
            $this->addError('editSpecialtyId', 'Choisis une spécialité existante ou saisis-en une nouvelle.');
            return;
        }

        $data = [
            'name' => $this->editName,
            'phone' => $this->editPhone ?: null,
            'role' => $this->editRole,
        ];

        if ($this->editNewPassword !== '') {
            $data['password'] = $this->editNewPassword;
        }

        $user->update($data);

        // Le rôle devient "médecin" et n'avait pas encore de fiche médecin :
        // on la crée dans la foulée pour que la personne apparaisse bien
        // dans les agendas.
        if ($becomingDoctor) {
            $specialtyId = $this->editSpecialtyId;
            if (! $specialtyId) {
                $specialtyId = Specialty::firstOrCreate(['name' => trim($this->editSpecialtyName)])->id;
            }

            Doctor::create([
                'user_id' => $user->id,
                'specialty_id' => $specialtyId,
                'title' => $this->editTitle,
                'teleconsultation_enabled' => $this->editTele,
            ]);
        } elseif ($user->doctor) {
            // Le rôle change et n'est plus "médecin", mais on garde sa fiche
            // médecin telle quelle : elle reste liée à son historique de
            // consultations/RDV passés, juste plus utilisée activement.
            $user->doctor->update([
                'specialty_id' => $this->editSpecialtyId ?: $user->doctor->specialty_id,
                'title' => $this->editTitle,
                'teleconsultation_enabled' => $this->editTele,
            ]);
        }

        $this->showEditModal = false;
        $this->dispatch('toast', message: 'Compte de ' . $user->name . ' mis à jour.');
    }

    // ---------- Activer / désactiver ----------

    public function askToggleActive(int $id, string $name, bool $currentlyActive)
    {
        if ($id === Auth::id()) {
            $this->dispatch('toast', message: 'Tu ne peux pas désactiver ton propre compte.');
            return;
        }

        $this->confirmTargetId = $id;
        $this->confirmAction = 'toggleActive';
        $this->confirmMessage = $currentlyActive
            ? 'Désactiver le compte de ' . $name . ' ? Il ne pourra plus se connecter, mais rien n\'est supprimé.'
            : 'Réactiver le compte de ' . $name . ' ?';
        $this->showConfirmModal = true;
    }

    public function closeConfirm()
    {
        $this->showConfirmModal = false;
    }

    public function runConfirmedAction()
    {
        if ($this->confirmAction === 'toggleActive') {
            $user = User::findOrFail($this->confirmTargetId);
            $user->update(['is_active' => ! $user->is_active]);
            $this->dispatch('toast', message: ($user->is_active ? 'Compte réactivé.' : 'Compte désactivé.'));
        }

        $this->showConfirmModal = false;
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->orderBy('name')
            ->simplePaginate(15);

        return view('livewire.users', [
            'users' => $users,
            'roles' => self::ROLES,
            'specialties' => Specialty::orderBy('name')->get(),
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
