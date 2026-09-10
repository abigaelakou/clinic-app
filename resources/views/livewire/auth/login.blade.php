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
</div>
