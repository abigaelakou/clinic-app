<div>
    <div class="page-head">
        <div>
            <h1>Dossiers patients</h1>
            <div class="date">Accès réservé au personnel soignant · Journalisation active</div>
        </div>
        <div style="display:flex;gap:10px;">
            @if($canManageTypes)
                <button class="btn ghost" wire:click="openNewType">🏷 + Type de consultation</button>
            @endif
            @if($canCreatePatient)
                <button class="btn ghost" wire:click="openImport">⬆ Importer (Excel)</button>
                <button class="btn" wire:click="openNewPatient">+ Nouvelle patiente</button>
            @endif
        </div>
    </div>

    <div class="grid-2" style="align-items:flex-start;">
        <div style="flex:1 1 280px; max-width:320px;">
            <div class="card">
                <div class="card-head" style="padding:16px 18px 10px;">
                    <h2 style="font-size:14.5px;">Patientes</h2>
                </div>
                <div style="padding:0 18px 12px;">
                    <input type="text" wire:model.live.debounce.200ms="search" placeholder="Rechercher un nom, un téléphone…"
                           style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                </div>
                @forelse($patients as $p)
                    <div class="pat-item {{ $selectedPatientId === $p->id ? 'active' : '' }}" wire:click="selectPatient({{ $p->id }})">
                        <div class="avatar" style="background:linear-gradient(135deg,#C0410C,#7a2707);">{{ strtoupper(substr($p->first_name,0,1)) }}</div>
                        <div>
                            <div class="pat-name">{{ $p->first_name }} {{ $p->last_name }}</div>
                            <div class="pat-meta">{{ $p->age() ? $p->age().' ans' : '' }} {{ $p->phone ? '· '.$p->phone : '' }}</div>
                        </div>
                    </div>
                @empty
                    <div style="padding:16px 18px;font-size:12.5px;color:var(--ink-soft);">Aucune patiente trouvée.</div>
                @endforelse
            </div>
        </div>

        <div style="flex:2.2 1 480px;">
            @if($selectedPatient)
                <div class="card">
                    <div class="patient-header">
                        <div class="avatar" style="width:52px;height:52px;font-size:18px;background:linear-gradient(135deg,#C0410C,#7a2707);">{{ strtoupper(substr($selectedPatient->first_name,0,1)) }}</div>
                        <div style="flex:1;">
                            <div class="patient-name">{{ $selectedPatient->first_name }} {{ $selectedPatient->last_name }}</div>
                            <div class="patient-sub">{{ $selectedPatient->age() ? $selectedPatient->age().' ans' : '' }} · {{ $selectedPatient->phone }}</div>
                        </div>
                        @if($canEditPatient)
                            <button class="btn ghost" style="padding:7px 12px;font-size:11.5px;" wire:click="openEditPatient">✏️ Modifier</button>
                        @endif
                        @if($canDeletePatient)
                            <button class="btn ghost" style="padding:7px 12px;font-size:11.5px;color:var(--crit);"
                                    wire:click="askConfirm('deletePatient', 'Retirer {{ $selectedPatient->first_name }} des dossiers patients ? Son historique (consultations, documents…) restera conservé, mais elle n\'apparaîtra plus dans la liste.')">🗑 Retirer</button>
                        @endif
                        <div class="audit-note">👁 Consulté par vous — {{ now()->format('d/m H:i') }}</div>
                    </div>

                    <div class="pat-tabs">
                        <div class="ptab {{ $activeTab === 'resume' ? 'active' : '' }}" wire:click="setTab('resume')">Résumé</div>
                        @if($canViewClinical)
                            <div class="ptab {{ $activeTab === 'consultations' ? 'active' : '' }}" wire:click="setTab('consultations')">Consultations</div>
                        @endif
                        <div class="ptab {{ $activeTab === 'documents' ? 'active' : '' }}" wire:click="setTab('documents')">Documents</div>
                        <div class="ptab {{ $activeTab === 'constantes' ? 'active' : '' }}" wire:click="setTab('constantes')">Constantes</div>
                        @if($canViewAuditLog)
                            <div class="ptab {{ $activeTab === 'journal' ? 'active' : '' }}" wire:click="setTab('journal')">Journal d'accès</div>
                        @endif
                    </div>

                    {{-- ===== Résumé ===== --}}
                    @if($activeTab === 'resume')
                        <div style="padding:4px 20px 20px;">
                            <div class="info-grid">
                                <div><div class="info-label">Contact</div><div class="info-val">{{ $selectedPatient->phone ?: '—' }}</div></div>
                                <div><div class="info-label">Contact d'urgence</div><div class="info-val">{{ $selectedPatient->emergency_contact_name ? $selectedPatient->emergency_contact_name.' — '.$selectedPatient->emergency_contact_phone : '—' }}</div></div>
                                <div><div class="info-label">Antécédents</div><div class="info-val">{{ $selectedPatient->declared_history ?: 'RAS déclaré' }}</div></div>
                                <div><div class="info-label">Sexe</div><div class="info-val">{{ $selectedPatient->sex === 'F' ? 'Féminin' : 'Masculin' }}</div></div>
                            </div>
                        </div>
                    @endif

                    {{-- ===== Consultations ===== --}}
                    @if($activeTab === 'consultations' && $canViewClinical)
                        <div style="padding:14px 20px 6px;display:flex;justify-content:flex-end;">
                            @if($canAddConsult)
                                <button class="btn" style="padding:8px 14px;font-size:12px;" wire:click="openConsult">+ Nouvelle consultation</button>
                            @endif
                        </div>
                        @forelse($consultations as $c)
                            <div class="consult-row">
                                <div class="consult-date">{{ $c->consulted_at->format('d M Y') }}</div>
                                <div class="consult-info">
                                    <b>
                                        @if($c->type)
                                            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;padding:0 5px;border-radius:5px;font-size:10.5px;font-weight:800;color:#fff;background:{{ $c->type->color }};margin-right:6px;vertical-align:middle;">{{ $c->type->code }}</span>
                                        @endif
                                        {{ $c->motif }}
                                    </b>
                                    <div class="prod-cat">
                                        {{ $c->doctor->user->name ?? '' }}
                                        @if($c->type) · {{ $c->type->label }} @endif
                                        @if($c->diagnostic) · {{ $c->diagnostic }} @endif
                                    </div>
                                </div>
                                @if($canAddConsult && auth()->user()->doctor && $c->doctor_id === auth()->user()->doctor->id)
                                    <div style="display:flex;gap:6px;margin-left:auto;flex:none;">
                                        <button class="btn ghost" style="padding:5px 9px;font-size:11px;" wire:click="openEditConsult({{ $c->id }})">Modifier</button>
                                        <button class="btn ghost" style="padding:5px 9px;font-size:11px;color:var(--crit);"
                                                wire:click="askConfirm('deleteConsult', 'Supprimer cette consultation ? Cette action est irréversible.', {{ $c->id }})">Supprimer</button>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">Aucune consultation enregistrée.</div>
                        @endforelse
                        @if(method_exists($consultations, 'hasPages') && $consultations->hasPages())
                            <div class="pager">
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ $consultations->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage('consultPage')">← Préc.</button>
                                <span class="pg-info">Page {{ $consultations->currentPage() }}</span>
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ ! $consultations->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage('consultPage')">Suiv. →</button>
                            </div>
                        @endif
                    @endif

                    {{-- ===== Documents ===== --}}
                    @if($activeTab === 'documents')
                        <div style="padding:14px 20px 6px;display:flex;justify-content:flex-end;">
                            @if($canAddDoc)
                                <button class="btn" style="padding:8px 14px;font-size:12px;" wire:click="openDoc">+ Ajouter un document</button>
                            @endif
                        </div>
                        @forelse($documents as $doc)
                            @php
                                $ext = strtolower(pathinfo($doc->file_path, PATHINFO_EXTENSION));
                                $fileIcon = match(true) {
                                    $ext === 'pdf' => '📄',
                                    in_array($ext, ['jpg','jpeg','png']) => '🖼️',
                                    in_array($ext, ['doc','docx']) => '📝',
                                    default => '📎',
                                };
                            @endphp
                            <div class="consult-row">
                                <div class="consult-date">{{ $doc->created_at->format('d M Y') }}</div>
                                <div class="consult-info">
                                    <b>{{ $fileIcon }} {{ $doc->title }}</b>
                                    <div class="prod-cat">
                                        {{ ucfirst($doc->type) }} · {{ strtoupper($ext) }} · par {{ $doc->uploadedBy->name ?? '' }}
                                        @if($doc->shared_with_patient)
                                            <span style="color:var(--ok);font-weight:700;"> · Partagé avec la patiente</span>
                                        @else
                                            <span style="color:var(--ink-faint);"> · Non partagé</span>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="btn ghost" style="padding:6px 10px;font-size:11px;">Ouvrir</a>
                            </div>
                        @empty
                            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">Aucun document.</div>
                        @endforelse
                        @if(method_exists($documents, 'hasPages') && $documents->hasPages())
                            <div class="pager">
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ $documents->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage('docPage')">← Préc.</button>
                                <span class="pg-info">Page {{ $documents->currentPage() }}</span>
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ ! $documents->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage('docPage')">Suiv. →</button>
                            </div>
                        @endif
                    @endif

                    {{-- ===== Constantes ===== --}}
                    @if($activeTab === 'constantes')
                        <div style="padding:14px 20px 6px;display:flex;justify-content:flex-end;">
                            @if($canAddVitals)
                                <button class="btn" style="padding:8px 14px;font-size:12px;" wire:click="openVitals">+ Ajouter des constantes</button>
                            @endif
                        </div>
                        @forelse($vitalsList as $v)
                            <div class="consult-row">
                                <div class="consult-date">{{ \Carbon\Carbon::parse($v->recorded_at)->format('d M Y H:i') }}</div>
                                <div class="consult-info">
                                    <b>
                                        @if($v->weight_kg) {{ $v->weight_kg }} kg @endif
                                        @if($v->height_cm) · {{ $v->height_cm }} cm @endif
                                        @if($v->blood_pressure) · TA {{ $v->blood_pressure }} @endif
                                    </b>
                                    <div class="prod-cat">
                                        @if($v->temperature_c) {{ $v->temperature_c }}°C @endif
                                        @if($v->heart_rate) · {{ $v->heart_rate }} bpm @endif
                                        · par {{ $v->recordedBy->name ?? '' }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">Aucune constante enregistrée.</div>
                        @endforelse
                        @if(method_exists($vitalsList, 'hasPages') && $vitalsList->hasPages())
                            <div class="pager">
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ $vitalsList->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage('vitalsPage')">← Préc.</button>
                                <span class="pg-info">Page {{ $vitalsList->currentPage() }}</span>
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ ! $vitalsList->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage('vitalsPage')">Suiv. →</button>
                            </div>
                        @endif
                    @endif

                    {{-- ===== Journal d'accès ===== --}}
                    @if($activeTab === 'journal' && $canViewAuditLog)
                        <div style="padding:14px 20px 4px;font-size:11.5px;color:var(--ink-faint);">
                            Qui a consulté ce dossier, et quand — pour la confidentialité (cahier §5.3.1).
                        </div>
                        @forelse($accessLogs as $log)
                            <div class="consult-row">
                                <div class="consult-date">{{ \Carbon\Carbon::parse($log->accessed_at)->format('d M Y H:i') }}</div>
                                <div class="consult-info">
                                    <b>{{ $log->user->name ?? 'Utilisateur supprimé' }}</b>
                                    <div class="prod-cat">{{ $log->user->role ?? '' }} · action : {{ $log->action }}</div>
                                </div>
                            </div>
                        @empty
                            <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">Aucun accès enregistré pour l'instant.</div>
                        @endforelse
                        @if(method_exists($accessLogs, 'hasPages') && $accessLogs->hasPages())
                            <div class="pager">
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ $accessLogs->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage('journalPage')">← Préc.</button>
                                <span class="pg-info">Page {{ $accessLogs->currentPage() }}</span>
                                <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ ! $accessLogs->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage('journalPage')">Suiv. →</button>
                            </div>
                        @endif
                    @endif
                </div>
            @else
                <div class="card" style="padding:60px 20px;text-align:center;color:var(--ink-soft);font-size:13px;">
                    Sélectionne une patiente dans la liste pour voir son dossier.
                </div>
            @endif
        </div>
    </div>

    {{-- ===== Modale Nouvelle consultation ===== --}}
    <div class="modal-backdrop {{ $showConsultModal ? 'active' : '' }}" wire:click.self="closeConsult">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">{{ $editingConsultId ? 'Modifier la consultation' : 'Nouvelle consultation' }}</h3>
                <button class="modal-close" wire:click="closeConsult">✕</button>
            </div>
            <form wire:submit="saveConsult">
                <div class="form-field">
                    <label>Type de consultation (optionnel)</label>
                    <div style="display:flex;gap:8px;">
                        <select wire:model="consultTypeId" style="flex:1;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                            <option value="">— Aucun —</option>
                            @foreach($consultationTypes as $t)
                                <option value="{{ $t->id }}">{{ $t->code }} — {{ $t->label }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn ghost" style="flex:none;padding:10px 12px;font-size:12px;" wire:click="openNewType">+ Type</button>
                    </div>
                </div>
                <div class="form-field">
                    <label>Motif</label>
                    <input type="text" wire:model="consultMotif" placeholder="Ex : suivi de grossesse, consultation gynéco...">
                    @error('consultMotif') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label>Diagnostic (optionnel)</label>
                    <input type="text" wire:model="consultDiagnostic">
                </div>
                <div class="form-field">
                    <label>Prescriptions (optionnel)</label>
                    <textarea wire:model="consultPrescriptions" rows="3" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;"></textarea>
                </div>
                <div class="form-field">
                    <label>Examens demandés (optionnel)</label>
                    <input type="text" wire:model="consultExams">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeConsult">Annuler</button>
                    <button type="submit" class="btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Nouveau document ===== --}}
    <div class="modal-backdrop {{ $showDocModal ? 'active' : '' }}" wire:click.self="closeDoc">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Ajouter un document</h3>
                <button class="modal-close" wire:click="closeDoc">✕</button>
            </div>
            <form wire:submit="saveDoc">
                <div class="form-field">
                    <label>Titre (optionnel)</label>
                    <input type="text" wire:model="docTitle" placeholder="Laisse vide pour utiliser le nom du fichier">
                    @error('docTitle') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label>Type</label>
                    <select wire:model="docType" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                        <option value="ordonnance">Ordonnance</option>
                        <option value="resultat_examen">Résultat d'examen</option>
                        <option value="compte_rendu">Compte-rendu</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Fichier(s)</label>
                    <input type="file" wire:model="docFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" multiple>
                    <div style="font-size:11px;color:var(--ink-faint);margin-top:4px;">
                        PDF, image (JPG/PNG) ou document Word — 25 Mo max par fichier. Tu peux en sélectionner plusieurs à la fois (Ctrl+clic ou Cmd+clic).
                    </div>
                    @error('docFile') <div class="field-error">{{ $message }}</div> @enderror
                    @error('docFile.*') <div class="field-error">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="docFile" style="font-size:11.5px;color:var(--ink-soft);margin-top:4px;">Envoi en cours…</div>
                </div>
                <div class="form-field">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" wire:model="docShared" style="width:auto;">
                        Partager avec la patiente (visible dans son espace patiente)
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeDoc">Annuler</button>
                    <button type="submit" class="btn">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Nouvelles constantes ===== --}}
    <div class="modal-backdrop {{ $showVitalsModal ? 'active' : '' }}" wire:click.self="closeVitals">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Ajouter des constantes</h3>
                <button class="modal-close" wire:click="closeVitals">✕</button>
            </div>
            <form wire:submit="saveVitals">
                <div class="form-row">
                    <div class="form-field">
                        <label>Poids (kg)</label>
                        <input type="number" step="0.1" wire:model="vitalsWeight">
                    </div>
                    <div class="form-field">
                        <label>Taille (cm)</label>
                        <input type="number" step="1" wire:model="vitalsHeight">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Tension artérielle</label>
                        <input type="text" wire:model="vitalsBp" placeholder="Ex : 120/80">
                    </div>
                    <div class="form-field">
                        <label>Température (°C)</label>
                        <input type="number" step="0.1" wire:model="vitalsTemp">
                    </div>
                </div>
                <div class="form-field">
                    <label>Fréquence cardiaque (bpm)</label>
                    <input type="number" step="1" wire:model="vitalsHeartRate">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeVitals">Annuler</button>
                    <button type="submit" class="btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Nouvelle patiente ===== --}}
    <div class="modal-backdrop {{ $showNewPatientModal ? 'active' : '' }}" wire:click.self="closeNewPatient">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Nouvelle patiente</h3>
                <button class="modal-close" wire:click="closeNewPatient">✕</button>
            </div>
            <form wire:submit="saveNewPatient">
                <div class="form-row">
                    <div class="form-field">
                        <label>Prénom</label>
                        <input type="text" wire:model="newFirstName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                        @error('newFirstName') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Nom (optionnel)</label>
                        <input type="text" wire:model="newLastName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Téléphone</label>
                        <input type="text" wire:model="newPhone" placeholder="07 XX XX XX XX" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                        @error('newPhone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Date de naissance</label>
                        <input type="date" wire:model="newDob">
                    </div>
                </div>
                <div class="form-field">
                    <label>Sexe</label>
                    <div class="type-toggle">
                        <input type="radio" id="sex-f" value="F" wire:model="newSex">
                        <label for="sex-f">Féminin</label>
                        <input type="radio" id="sex-m" value="M" wire:model="newSex">
                        <label for="sex-m">Masculin</label>
                    </div>
                </div>
                <div class="form-field">
                    <label>E-mail (optionnel)</label>
                    <input type="email" wire:model="newEmail">
                    @error('newEmail') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label>Adresse (optionnel)</label>
                    <input type="text" wire:model="newAddress">
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Contact d'urgence — nom</label>
                        <input type="text" wire:model="newEmergencyName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    </div>
                    <div class="form-field">
                        <label>Contact d'urgence — tél.</label>
                        <input type="text" wire:model="newEmergencyPhone" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                    </div>
                </div>
                <div class="form-field">
                    <label>Antécédents déclarés (optionnel)</label>
                    <input type="text" wire:model="newHistory" placeholder="Ex : RAS, allergies connues...">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeNewPatient">Annuler</button>
                    <button type="submit" class="btn">Créer le dossier</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Import Excel ===== --}}
    <div class="modal-backdrop {{ $showImportModal ? 'active' : '' }}" wire:click.self="closeImport">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Importer des patientes (Excel)</h3>
                <button class="modal-close" wire:click="closeImport">✕</button>
            </div>

            <div style="font-size:12.5px;color:var(--ink-soft);margin-bottom:14px;line-height:1.5;">
                Télécharge d'abord le modèle pour être sûr que les colonnes correspondent,
                copie tes données dedans (une ligne par patiente), puis importe le fichier rempli.
            </div>

            <a href="{{ route('patients.import-template') }}" class="btn ghost" style="width:100%;justify-content:center;margin-bottom:16px;">⬇ Télécharger le modèle</a>

            <form wire:submit="saveImport">
                <div class="form-field">
                    <label>Fichier rempli (.xlsx)</label>
                    <input type="file" wire:model="importFile" accept=".xlsx,.xls">
                    @error('importFile') <div class="field-error">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="importFile" style="font-size:11.5px;color:var(--ink-soft);margin-top:4px;">Envoi en cours…</div>
                </div>

                @if(count($importSummary) > 0)
                    <div style="background:var(--ok-tint);border-radius:var(--radius-s);padding:12px 14px;margin:12px 0;font-size:12.5px;">
                        <div style="font-weight:700;color:var(--ok);">{{ $importSummary['created'] }} patiente(s) importée(s)</div>
                        @if($importSummary['skipped'] > 0)
                            <div style="color:var(--warn);margin-top:4px;">{{ $importSummary['skipped'] }} ligne(s) ignorée(s) (doublon ou champ manquant)</div>
                            @foreach($importSummary['errors'] as $err)
                                <div style="color:var(--ink-soft);font-size:11.5px;margin-top:2px;">{{ $err }}</div>
                            @endforeach
                        @endif
                    </div>
                @endif

                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeImport">Fermer</button>
                    <button type="submit" class="btn" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveImport">Importer</span>
                        <span wire:loading wire:target="saveImport">Import en cours…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Modifier patiente ===== --}}
    <div class="modal-backdrop {{ $showEditPatientModal ? 'active' : '' }}" wire:click.self="closeEditPatient">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Modifier la fiche</h3>
                <button class="modal-close" wire:click="closeEditPatient">✕</button>
            </div>
            <form wire:submit="saveEditPatient">
                <div class="form-row">
                    <div class="form-field">
                        <label>Prénom</label>
                        <input type="text" wire:model="editFirstName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                        @error('editFirstName') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Nom (optionnel)</label>
                        <input type="text" wire:model="editLastName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Téléphone</label>
                        <input type="text" wire:model="editPhone" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                        @error('editPhone') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Date de naissance</label>
                        <input type="date" wire:model="editDob">
                    </div>
                </div>
                <div class="form-field">
                    <label>Sexe</label>
                    <div class="type-toggle">
                        <input type="radio" id="edit-sex-f" value="F" wire:model="editSex">
                        <label for="edit-sex-f">Féminin</label>
                        <input type="radio" id="edit-sex-m" value="M" wire:model="editSex">
                        <label for="edit-sex-m">Masculin</label>
                    </div>
                </div>
                <div class="form-field">
                    <label>E-mail (optionnel)</label>
                    <input type="email" wire:model="editEmail">
                    @error('editEmail') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-field">
                    <label>Adresse (optionnel)</label>
                    <input type="text" wire:model="editAddress">
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Contact d'urgence — nom</label>
                        <input type="text" wire:model="editEmergencyName" x-on:input="$el.value = $el.value.replace(/[^\p{L}\s\-']/gu, '')">
                    </div>
                    <div class="form-field">
                        <label>Contact d'urgence — tél.</label>
                        <input type="text" wire:model="editEmergencyPhone" x-on:input="$el.value = $el.value.replace(/[^0-9+\-\s]/g, '')">
                    </div>
                </div>
                <div class="form-field">
                    <label>Antécédents déclarés (optionnel)</label>
                    <input type="text" wire:model="editHistory">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeEditPatient">Annuler</button>
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

    {{-- ===== Modale Nouveau type de consultation ===== --}}
    <div class="modal-backdrop {{ $showNewTypeModal ? 'active' : '' }}" wire:click.self="closeNewType">
        <div class="modal-card" style="max-width:380px;">
            <div class="modal-head">
                <h3 class="serif">Nouveau type de consultation</h3>
                <button class="modal-close" wire:click="closeNewType">✕</button>
            </div>
            <form wire:submit="saveNewType">
                <div class="form-row">
                    <div class="form-field" style="flex:0 0 100px;">
                        <label>Code</label>
                        <input type="text" wire:model="newTypeCode" placeholder="Ex : G" maxlength="10" style="text-transform:uppercase;">
                        @error('newTypeCode') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Libellé</label>
                        <input type="text" wire:model="newTypeLabel" placeholder="Ex : Grossesse">
                        @error('newTypeLabel') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-field">
                    <label>Couleur du badge</label>
                    <input type="color" wire:model="newTypeColor" style="width:60px;height:38px;padding:2px;border-radius:8px;border:1px solid var(--line);cursor:pointer;">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeNewType">Annuler</button>
                    <button type="submit" class="btn">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>
