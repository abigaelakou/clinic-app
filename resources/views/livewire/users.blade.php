<div>
    <div class="page-head">
        <div>
            <h1>Utilisateurs & droits</h1>
            <div class="date">Comptes du personnel — réservé à l'administrateur</div>
        </div>
        <button class="btn" wire:click="openNew">+ Nouveau compte</button>
    </div>

    <div class="card">
        <div class="card-head" style="padding:16px 18px 10px;">
            <input type="text" wire:model.live.debounce.200ms="search" placeholder="Rechercher un nom, un e-mail…"
                   style="width:280px;padding:9px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
        </div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Nom</th><th>E-mail</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td><div class="prod-name">{{ $u->name }}</div>{{ $u->phone }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ $roles[$u->role] ?? $u->role }}</td>
                        <td>
                            @if($u->is_active)
                                <span class="status-dot ok">Actif</span>
                            @else
                                <span class="status-dot crit">Désactivé</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;" wire:click="openEdit({{ $u->id }})">Modifier</button>
                            <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;{{ $u->is_active ? 'color:var(--crit);' : 'color:var(--ok);' }}"
                                    wire:click="askToggleActive({{ $u->id }}, '{{ addslashes($u->name) }}', {{ $u->is_active ? 'true' : 'false' }})">
                                {{ $u->is_active ? 'Désactiver' : 'Réactiver' }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;color:var(--ink-soft);">Aucun utilisateur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @if($users->hasPages())
            <div class="pager">
                <button class="btn ghost" style="padding:6px 12px;font-size:11.5px;{{ $users->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage">← Précédent</button>
                <span class="pg-info">Page {{ $users->currentPage() }}</span>
                <button class="btn ghost" style="padding:6px 12px;font-size:11.5px;{{ ! $users->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage">Suivant →</button>
            </div>
        @endif
    </div>

    {{-- ===== Modale Nouveau compte ===== --}}
    <div class="modal-backdrop {{ $showNewModal ? 'active' : '' }}" wire:click.self="closeNew">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Nouveau compte</h3>
                <button class="modal-close" wire:click="closeNew">✕</button>
            </div>
            <form wire:submit="saveNew">
                <div class="form-field">
                    <label>Nom complet</label>
                    <input type="text" wire:model="newName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    @error('newName') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>E-mail</label>
                        <input type="email" wire:model="newEmail">
                        @error('newEmail') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Téléphone (optionnel)</label>
                        <input type="text" wire:model="newPhone" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                        @error('newPhone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-field">
                    <label>Rôle</label>
                    <select wire:model.live="newRole" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if($newRole === 'medecin')
                    <div class="form-row">
                        <div class="form-field" style="flex:0 0 90px;">
                            <label>Titre</label>
                            <select wire:model="newTitle" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                                <option value="Dr">Dr</option>
                                <option value="Pr">Pr</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Spécialité existante</label>
                            <select wire:model="newSpecialtyId" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                                <option value="">— Choisir —</option>
                                @foreach($specialties as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-field">
                        <label>...ou nouvelle spécialité</label>
                        <input type="text" wire:model="newSpecialtyName" placeholder="Ex : Pédiatrie">
                        @error('newSpecialtyId') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" wire:model="newTele" style="width:auto;">
                            Peut faire des téléconsultations
                        </label>
                    </div>
                @endif

                <div class="form-field" x-data="{ show: false }">
                    <label>Mot de passe initial</label>
                    <div style="position:relative;">
                        <input :type="show ? 'text' : 'password'" wire:model="newPassword" placeholder="À communiquer toi-même à la personne" style="padding-right:38px;width:100%;">
                        <button type="button" @click="show = !show" tabindex="-1"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:15px;color:var(--ink-faint);padding:4px;">
                            <span x-show="!show">👁</span><span x-show="show" x-cloak>🙈</span>
                        </button>
                    </div>
                    @error('newPassword') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeNew">Annuler</button>
                    <button type="submit" class="btn">Créer le compte</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Modifier compte ===== --}}
    <div class="modal-backdrop {{ $showEditModal ? 'active' : '' }}" wire:click.self="closeEdit">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Modifier le compte</h3>
                <button class="modal-close" wire:click="closeEdit">✕</button>
            </div>
            <form wire:submit="saveEdit">
                <div class="form-field">
                    <label>Nom complet</label>
                    <input type="text" wire:model="editName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    @error('editName') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label>Téléphone (optionnel)</label>
                    <input type="text" wire:model="editPhone" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                    @error('editPhone') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label>Rôle</label>
                    <select wire:model.live="editRole" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                        @foreach($roles as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if($editRole === 'medecin')
                    <div class="form-row">
                        <div class="form-field" style="flex:0 0 90px;">
                            <label>Titre</label>
                            <select wire:model="editTitle" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                                <option value="Dr">Dr</option>
                                <option value="Pr">Pr</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label>Spécialité existante</label>
                            <select wire:model="editSpecialtyId" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                                <option value="">— Choisir —</option>
                                @foreach($specialties as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-field">
                        <label>...ou nouvelle spécialité</label>
                        <input type="text" wire:model="editSpecialtyName" placeholder="Ex : Pédiatrie">
                        @error('editSpecialtyId') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" wire:model="editTele" style="width:auto;">
                            Peut faire des téléconsultations
                        </label>
                    </div>
                @else
                    <div style="font-size:11px;color:var(--ink-faint);margin:-6px 0 14px;">
                        Ce rôle n'est pas "Médecin" — aucune fiche médecin n'est créée ou modifiée.
                    </div>
                @endif

                <div class="form-field" x-data="{ show: false }">
                    <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <div style="position:relative;">
                        <input :type="show ? 'text' : 'password'" wire:model="editNewPassword" placeholder="Laisser vide pour conserver l'actuel" style="padding-right:38px;width:100%;">
                        <button type="button" @click="show = !show" tabindex="-1"
                                style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:15px;color:var(--ink-faint);padding:4px;">
                            <span x-show="!show">👁</span><span x-show="show" x-cloak>🙈</span>
                        </button>
                    </div>
                    @error('editNewPassword') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeEdit">Annuler</button>
                    <button type="submit" class="btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale de confirmation générique ===== --}}
    <div class="modal-backdrop {{ $showConfirmModal ? 'active' : '' }}" wire:click.self="closeConfirm">
        <div class="modal-card" style="max-width:380px;">
            <div class="confirm-icon">⚠</div>
            <div class="confirm-text">{{ $confirmMessage }}</div>
            <div class="form-actions">
                <button type="button" class="btn ghost" wire:click="closeConfirm">Annuler</button>
                <button type="button" class="btn" style="background:var(--crit);" wire:click="runConfirmedAction">Confirmer</button>
            </div>
        </div>
    </div>
</div>
