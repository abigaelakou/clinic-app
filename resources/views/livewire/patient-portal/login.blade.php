<div class="pp-guest">
    <div class="pp-guest-card">
        <div class="pp-logo">F</div>
        <h1 class="pp-title serif">Mon espace</h1>
        <p class="pp-sub">CLINIQUE FAME</p>

        @if($errorMessage)
            <div class="pp-banner">{{ $errorMessage }}</div>
        @endif

        <form wire:submit="login">
            <div class="pp-field">
                <label>Téléphone</label>
                <input type="text" wire:model="phone" placeholder="07 XX XX XX XX">
                @error('phone') <div class="pp-error">{{ $message }}</div> @enderror
            </div>
            <div class="pp-field">
                <label>Mot de passe</label>
                <input type="password" wire:model="password">
                @error('password') <div class="pp-error">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="pp-btn">Se connecter</button>
        </form>

        <div class="pp-alt">Pas encore de compte ? <a href="{{ route('patient.register') }}">S'inscrire</a></div>
    </div>
</div>
