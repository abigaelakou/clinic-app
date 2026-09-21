<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $phone = '';

    public $newAvatar = null;

    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPassword_confirmation = '';

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->phone = $user->phone ?? '';
    }

    public function updateInfo()
    {
        $this->validate([
            'name' => ['required', 'min:2', 'regex:/^[\p{L}\s\-\']+$/u'],
            'phone' => ['nullable', 'regex:/^[0-9+\-\s]*$/'],
        ], [
            'name.regex' => 'Le nom ne doit contenir que des lettres.',
            'phone.regex' => 'Le téléphone ne doit contenir que des chiffres.',
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'phone' => $this->phone ?: null,
        ]);

        $this->dispatch('toast', message: 'Profil mis à jour.');
    }

    public function updateAvatar()
    {
        $this->validate([
            'newAvatar' => 'required|image|max:4096',
        ], [], ['newAvatar' => 'photo']);

        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $this->newAvatar->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        $this->newAvatar = null;
        $this->dispatch('toast', message: 'Photo de profil mise à jour.');
    }

    public function removeAvatar()
    {
        $user = Auth::user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        $this->dispatch('toast', message: 'Photo de profil retirée.');
    }

    public function updatePassword()
    {
        $this->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|min:6|confirmed',
        ], [], [
            'currentPassword' => 'mot de passe actuel',
            'newPassword' => 'nouveau mot de passe',
        ]);

        if (! Hash::check($this->currentPassword, Auth::user()->password)) {
            $this->addError('currentPassword', 'Mot de passe actuel incorrect.');
            return;
        }

        Auth::user()->update(['password' => $this->newPassword]);

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPassword_confirmation = '';

        $this->dispatch('toast', message: 'Mot de passe changé.');
    }

    public function render()
    {
        return view('livewire.profile', [
            'user' => Auth::user()->fresh(),
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
