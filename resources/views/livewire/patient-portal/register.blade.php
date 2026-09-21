<div class="pp-guest">
    <div class="pp-guest-card">
        <div class="pp-logo">F</div>

        @if($duplicateFound)
            <h1 class="pp-title serif">On te connaît déjà !</h1>
            <p class="pp-sub">CLINIQUE FAME</p>

            @if($duplicateAlreadyClaimed)
                <div class="pp-banner" style="text-align:left;">
                    Ce numéro est déjà associé à un compte ({{ $duplicateName }}). Si c'est toi, connecte-toi directement.
                </div>
                <a href="{{ route('patient.login') }}" class="pp-btn" style="display:block;text-align:center;text-decoration:none;">Se connecter</a>
                <button type="button" class="pp-btn ghost" wire:click="notMe" style="margin-top:8px;">Ce n'est pas moi, recommencer</button>
            @else
                <div class="pp-banner" style="text-align:left;background:var(--warn-tint);color:var(--warn);">
                    Un dossier existe déjà à la clinique pour <b>{{ $duplicateName }}</b> avec ce numéro. Est-ce bien toi ?
                </div>
                <button type="button" class="pp-btn" wire:click="claimAccount">Oui, c'est moi — activer mon accès en ligne</button>
                <button type="button" class="pp-btn ghost" wire:click="notMe" style="margin-top:8px;">Non, ce n'est pas moi</button>
                <div class="pp-alt">Pas ton dossier ? Contacte la réception de la clinique pour vérifier.</div>
            @endif
        @else
            <h1 class="pp-title serif">Créer mon espace</h1>
            <p class="pp-sub">CLINIQUE FAME — suivi de tes rendez-vous et documents</p>

            <form wire:submit="register">
                <div class="pp-row">
                    <div class="pp-field">
                        <label>Prénom</label>
                        <input type="text" wire:model="firstName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                        @error('firstName') <div class="pp-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="pp-field">
                        <label>Nom</label>
                        <input type="text" wire:model="lastName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    </div>
                </div>
                <div class="pp-field">
                    <label>Téléphone</label>
                    <input type="text" wire:model="phone" placeholder="07 XX XX XX XX" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                    @error('phone') <div class="pp-error">{{ $message }}</div> @enderror
                </div>
                <div class="pp-row">
                    <div class="pp-field">
                        <label>Date de naissance</label>
                        <input type="date" wire:model="dob">
                    </div>
                    <div class="pp-field">
                        <label>Sexe</label>
                        <select wire:model="sex">
                            <option value="F">Féminin</option>
                            <option value="M">Masculin</option>
                        </select>
                    </div>
                </div>
                <div class="pp-field">
                    <label>Mot de passe</label>
                    <input type="password" wire:model="password">
                    @error('password') <div class="pp-error">{{ $message }}</div> @enderror
                </div>
                <div class="pp-field">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" wire:model="password_confirmation">
                </div>

                <button type="submit" class="pp-btn">Créer mon compte</button>
            </form>

            <div class="pp-alt">Déjà inscrite ? <a href="{{ route('patient.login') }}">Se connecter</a></div>
        @endif
    </div>
</div>
