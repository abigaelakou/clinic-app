<div>
    <div class="page-head">
        <div>
            <h1>Mon profil</h1>
            <div class="date">{{ $user->name }} · {{ $user->email }}</div>
        </div>
    </div>

    <div class="grid-2" style="align-items:flex-start;">
        <div style="flex:1 1 260px; max-width:280px;">
            <div class="card" style="padding:22px;text-align:center;">
                @if($newAvatar)
                    {{-- Prévisualisation de la photo tout juste sélectionnée, pas encore enregistrée --}}
                    <img src="{{ $newAvatar->temporaryUrl() }}" alt="Prévisualisation"
                         style="width:96px;height:96px;border-radius:50%;object-fit:cover;margin:0 auto 6px;display:block;border:3px solid var(--clay);">
                    <div style="font-size:11px;color:var(--clay);font-weight:600;margin-bottom:10px;">Aperçu — pas encore enregistré</div>
                @elseif($user->avatar_path)
                    <img src="{{ tenant_asset($user->avatar_path) }}" alt="Photo de profil"
                         style="width:96px;height:96px;border-radius:50%;object-fit:cover;margin:0 auto 14px;display:block;border:3px solid var(--stone);">
                @else
                    <div class="avatar" style="width:96px;height:96px;font-size:32px;margin:0 auto 14px;background:linear-gradient(135deg,#C0410C,#7a2707);">
                        {{ strtoupper(substr($user->name,0,1)) }}
                    </div>
                @endif

                <form wire:submit="updateAvatar">
                    <input type="file" wire:model="newAvatar" accept="image/*" style="font-size:12px;">
                    <div wire:loading wire:target="newAvatar" style="font-size:11px;color:var(--ink-soft);margin-top:4px;">Envoi…</div>
                    @error('newAvatar') <div class="field-error">{{ $message }}</div> @enderror

                    @if($newAvatar)
                        <button type="submit" class="btn" style="width:100%;margin-top:10px;padding:8px;font-size:12px;">Enregistrer la photo</button>
                    @endif
                </form>

                @if($user->avatar_path)
                    <button class="btn ghost" style="width:100%;margin-top:8px;padding:8px;font-size:12px;color:var(--crit);" wire:click="removeAvatar">Retirer la photo</button>
                @endif
            </div>
        </div>

        <div style="flex:2.2 1 420px;">
            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><h2>Mes informations</h2></div>
                <form wire:submit="updateInfo" style="padding:0 20px 20px;">
                    <div class="form-field">
                        <label>Nom complet</label>
                        <input type="text" wire:model="name" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                        @error('name') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Téléphone</label>
                        <input type="text" wire:model="phone" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                        @error('phone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>E-mail</label>
                        <input type="text" value="{{ $user->email }}" disabled style="opacity:0.6;">
                        <div style="font-size:11px;color:var(--ink-faint);margin-top:4px;">Pour changer ton e-mail, demande à un administrateur.</div>
                    </div>
                    <div class="form-actions" style="justify-content:flex-start;">
                        <button type="submit" class="btn">Enregistrer</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-head"><h2>Changer mon mot de passe</h2></div>
                <form wire:submit="updatePassword" style="padding:0 20px 20px;">
                    <div class="form-field" x-data="{ show: false }">
                        <label>Mot de passe actuel</label>
                        <div style="position:relative;">
                            <input :type="show ? 'text' : 'password'" wire:model="currentPassword" style="padding-right:38px;width:100%;">
                            <button type="button" @click="show = !show" tabindex="-1"
                                    style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:15px;color:var(--ink-faint);padding:4px;">
                                <span x-show="!show">👁</span><span x-show="show" x-cloak>🙈</span>
                            </button>
                        </div>
                        @error('currentPassword') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-field" x-data="{ show: false }">
                            <label>Nouveau mot de passe</label>
                            <div style="position:relative;">
                                <input :type="show ? 'text' : 'password'" wire:model="newPassword" style="padding-right:38px;width:100%;">
                                <button type="button" @click="show = !show" tabindex="-1"
                                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:15px;color:var(--ink-faint);padding:4px;">
                                    <span x-show="!show">👁</span><span x-show="show" x-cloak>🙈</span>
                                </button>
                            </div>
                            @error('newPassword') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-field" x-data="{ show: false }">
                            <label>Confirmer le nouveau mot de passe</label>
                            <div style="position:relative;">
                                <input :type="show ? 'text' : 'password'" wire:model="newPassword_confirmation" style="padding-right:38px;width:100%;">
                                <button type="button" @click="show = !show" tabindex="-1"
                                        style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:15px;color:var(--ink-faint);padding:4px;">
                                    <span x-show="!show">👁</span><span x-show="show" x-cloak>🙈</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="form-actions" style="justify-content:flex-start;">
                        <button type="submit" class="btn">Changer le mot de passe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
