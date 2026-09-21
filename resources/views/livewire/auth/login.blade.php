<div>
    @if($error)
        <div class="guest-error">{{ $error }}</div>
    @endif

    <form wire:submit="submit">
        <div class="field">
            <label>Adresse e-mail</label>
            <input type="email" wire:model="email" autofocus autocomplete="username">
            @error('email') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label>Mot de passe</label>
            <input type="password" wire:model="password" autocomplete="current-password">
            @error('password') <div class="field-error">{{ $message }}</div> @enderror
        </div>

        <label class="remember-row">
            <input type="checkbox" wire:model="remember">
            Se souvenir de moi
        </label>

        <button type="submit" class="submit-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>Se connecter</span>
            <span wire:loading>Connexion…</span>
        </button>
    </form>

    <div style="text-align:center;margin-top:20px;font-size:12.5px;color:var(--ink-faint);">
        Tu es une patiente ? <a href="{{ route('patient.login') }}" style="color:var(--clay);font-weight:600;text-decoration:none;">Accède à ton espace</a>
    </div>
</div>
