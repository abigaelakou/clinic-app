<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    public string $error = '';

    public function submit()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required|min:4',
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->error = "Identifiants incorrects. Vérifie ton e-mail et ton mot de passe.";
            return;
        }

        request()->session()->regenerate();

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
