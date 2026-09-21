<div>
    <div class="pp-header">
        <div class="pp-header-top">
            <div class="pp-logo">F</div>
            <button class="pp-logout" wire:click="logout">Déconnexion ↩</button>
        </div>
        @php
            $hour = now()->hour;
            $greetEmoji = $hour < 12 ? '☀️' : ($hour < 18 ? '🌤️' : '🌙');
            $greetWord = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
        @endphp
        <div class="pp-greeting serif"><span class="pp-greeting-emoji">{{ $greetEmoji }}</span> {{ $greetWord }} {{ $patient->first_name }}</div>
        <div class="pp-greeting-sub">CLINIQUE FAME · Prends soin de toi ✨</div>
    </div>

    <div class="pp-container">
        @if(! $patient->identity_verified)
            <div class="pp-banner" style="text-align:left;">
                @if($patient->id_document_path)
                    ✓ Pièce d'identité envoyée — vérification à faire lors de ta prochaine visite à la clinique.
                @else
                    ⓘ Ton identité n'est pas encore vérifiée. Présente-toi à l'accueil avec une pièce d'identité, ou envoie-la dès maintenant ci-dessous.
                @endif
            </div>

            @if(! $patient->id_document_path)
                <div class="pp-card">
                    <div class="pp-card-head"><h2><span class="pp-card-icon" style="background:var(--gold-tint);">🪪</span>Envoyer ma pièce d'identité</h2></div>
                    <form wire:submit="uploadIdDocument">
                        <input type="file" wire:model="idDocument" accept=".pdf,.jpg,.jpeg,.png" style="font-size:13px;">
                        <div style="font-size:11px;color:var(--ink-faint);margin-top:4px;">Carte d'identité, passeport ou extrait de naissance — PDF ou image, 8 Mo max.</div>
                        @error('idDocument') <div class="pp-error">{{ $message }}</div> @enderror
                        <div wire:loading wire:target="idDocument" style="font-size:11.5px;color:var(--ink-soft);margin-top:4px;">Envoi…</div>
                        @if($idDocument)
                            <button type="submit" class="pp-btn" style="margin-top:10px;">Envoyer</button>
                        @endif
                    </form>
                </div>
            @endif
        @else
            <div class="pp-allgood">✓ Ton identité est vérifiée — tout est en ordre !</div>
        @endif

        <div class="pp-card">
            <div class="pp-card-head"><h2><span class="pp-card-icon i-appt">📅</span>Mes prochains rendez-vous</h2></div>
            @forelse($upcoming as $appt)
                <div class="pp-appt">
                    <div class="pp-appt-date">
                        <div class="d">{{ $appt->scheduled_at->format('d') }}</div>
                        <div class="m">{{ $appt->scheduled_at->translatedFormat('M') }}</div>
                    </div>
                    <div class="pp-appt-info" style="flex:1;">
                        <b>{{ $appt->doctor->user->name ?? '' }}</b>
                        <div>{{ $appt->scheduled_at->format('H:i') }} · {{ $appt->reason }}</div>
                        @if($appt->status === 'pending')
                            <span class="pp-tag pending">En attente de confirmation</span>
                        @elseif($appt->status === 'rescheduled')
                            <span class="pp-tag pending" style="background:var(--warn-tint);color:var(--warn);">Nouvelle date proposée</span>
                            <div style="display:flex;gap:8px;margin-top:8px;">
                                <button wire:click="acceptReschedule({{ $appt->id }})" class="pp-btn" style="width:auto;padding:7px 14px;font-size:12px;margin:0;">✓ Accepter</button>
                                <button wire:click="openProposeAlt({{ $appt->id }})" class="pp-btn ghost" style="width:auto;padding:7px 14px;font-size:12px;margin:0;">Autre date</button>
                            </div>
                        @else
                            <span class="pp-tag confirmed">Confirmé</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="pp-empty"><span class="e-icon">🗓️</span>Aucun rendez-vous à venir pour l'instant.</div>
            @endforelse
            @if($upcoming->hasPages())
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;margin-top:6px;border-top:1px solid var(--line);">
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ $upcoming->onFirstPage() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="previousPage('apptPage')">← Préc.</button>
                    <span style="font-size:11px;color:var(--ink-faint);">Page {{ $upcoming->currentPage() }}</span>
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ ! $upcoming->hasMorePages() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="nextPage('apptPage')">Suiv. →</button>
                </div>
            @endif
        </div>

        <div class="pp-card">
            <div class="pp-card-head"><h2><span class="pp-card-icon i-doc">📄</span>Mes documents</h2></div>
            @forelse($documents as $doc)
                <div class="pp-doc">
                    <span>{{ $doc->title }}</span>
                    <a href="{{ tenant_asset($doc->file_path) }}" target="_blank">Ouvrir</a>
                </div>
            @empty
                <div class="pp-empty"><span class="e-icon">📭</span>Aucun document partagé avec toi pour l'instant.</div>
            @endforelse
            @if($documents->hasPages())
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;margin-top:6px;border-top:1px solid var(--line);">
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ $documents->onFirstPage() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="previousPage('docPage')">← Préc.</button>
                    <span style="font-size:11px;color:var(--ink-faint);">Page {{ $documents->currentPage() }}</span>
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ ! $documents->hasMorePages() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="nextPage('docPage')">Suiv. →</button>
                </div>
            @endif
        </div>

        <div class="pp-card">
            <div class="pp-card-head"><h2><span class="pp-card-icon i-consult">🩺</span>Mes consultations passées</h2></div>
            @forelse($consultations as $c)
                <div class="pp-doc" style="align-items:flex-start;">
                    <div>
                        <div style="font-weight:600;">{{ $c->motif }}</div>
                        <div style="font-size:11.5px;color:var(--ink-faint);margin-top:2px;">
                            {{ $c->consulted_at->translatedFormat('d M Y') }} · {{ $c->doctor->user->name ?? '' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="pp-empty"><span class="e-icon">🌱</span>Aucune consultation enregistrée pour l'instant.</div>
            @endforelse
            @if($consultations->hasPages())
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;margin-top:6px;border-top:1px solid var(--line);">
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ $consultations->onFirstPage() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="previousPage('consultPage')">← Préc.</button>
                    <span style="font-size:11px;color:var(--ink-faint);">Page {{ $consultations->currentPage() }}</span>
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ ! $consultations->hasMorePages() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="nextPage('consultPage')">Suiv. →</button>
                </div>
            @endif
        </div>

        <div class="pp-card">
            <div class="pp-card-head"><h2><span class="pp-card-icon i-vitals">📊</span>Mes constantes</h2></div>
            @forelse($vitals as $v)
                <div class="pp-doc" style="align-items:flex-start;">
                    <div>
                        <div style="font-weight:600;">
                            @if($v->weight_kg) {{ $v->weight_kg }} kg @endif
                            @if($v->blood_pressure) · TA {{ $v->blood_pressure }} @endif
                        </div>
                        <div style="font-size:11.5px;color:var(--ink-faint);margin-top:2px;">
                            {{ \Carbon\Carbon::parse($v->recorded_at)->translatedFormat('d M Y') }}
                            @if($v->temperature_c) · {{ $v->temperature_c }}°C @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="pp-empty"><span class="e-icon">💚</span>Aucune constante enregistrée pour l'instant.</div>
            @endforelse
            @if($vitals->hasPages())
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:10px;margin-top:6px;border-top:1px solid var(--line);">
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ $vitals->onFirstPage() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="previousPage('vitalsPage')">← Préc.</button>
                    <span style="font-size:11px;color:var(--ink-faint);">Page {{ $vitals->currentPage() }}</span>
                    <button class="pp-btn ghost" style="width:auto;padding:6px 12px;font-size:11.5px;margin:0;{{ ! $vitals->hasMorePages() ? 'opacity:.4;pointer-events:none;' : '' }}" wire:click="nextPage('vitalsPage')">Suiv. →</button>
                </div>
            @endif
        </div>
    </div>

    <button class="pp-fab" wire:click="openBook">+ Demander un rendez-vous</button>

    {{-- ===== Modale demande de RDV ===== --}}
    <div class="pp-modal-backdrop {{ $showBookModal ? 'active' : '' }}" wire:click.self="closeBook">
        <div class="pp-modal">
            <div class="pp-modal-head">
                <h3 class="serif">Demander un rendez-vous</h3>
                <button class="pp-modal-close" wire:click="closeBook">✕</button>
            </div>
            <form wire:submit="saveBook">
                <div class="pp-field">
                    <label>Médecin</label>
                    @if($bookDoctorId)
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border-radius:9px;border:1px solid var(--line);background:var(--stone);">
                            <span style="font-size:14px;">✓ {{ $bookDoctorName }}</span>
                            <button type="button" wire:click="$set('bookDoctorId', null)" style="background:none;border:none;color:var(--clay);font-size:12px;font-weight:600;cursor:pointer;">Changer</button>
                        </div>
                    @else
                        <div x-data="{ open: true }">
                            <input type="text" wire:model.live.debounce.150ms="doctorSearch" placeholder="Rechercher un médecin par nom…" autocomplete="off">
                            <div style="margin-top:6px;border:1px solid var(--line);border-radius:9px;overflow:hidden;max-height:220px;overflow-y:auto;">
                                @forelse($filteredDoctors as $doc)
                                    <div wire:click="selectDoctor({{ $doc->id }})"
                                         style="padding:10px 13px;font-size:13.5px;cursor:pointer;border-bottom:1px solid var(--line);">
                                        {{ $doc->user->name ?? '' }} <span style="color:var(--ink-faint);font-size:12px;">— {{ $doc->specialty->name ?? '' }}</span>
                                    </div>
                                @empty
                                    <div style="padding:12px;font-size:12.5px;color:var(--ink-faint);text-align:center;">Aucun médecin trouvé.</div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                    @error('bookDoctorId') <div class="pp-error">{{ $message }}</div> @enderror
                </div>
                <div class="pp-row">
                    <div class="pp-field">
                        <label>Date souhaitée</label>
                        <input type="date" wire:model.live="bookDate">
                        @error('bookDate') <div class="pp-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="pp-field">
                        <label>Heure souhaitée</label>
                        <input type="time" wire:model="bookTime">
                        @error('bookTime') <div class="pp-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                @if($selectedDateUnavailable)
                    <div class="pp-banner" style="text-align:left;background:var(--warn-tint);color:var(--warn);">
                        ⚠ {{ $bookDoctorName }} a indiqué être indisponible ce jour-là — choisis une autre date.
                    </div>
                @endif
                <div class="pp-field">
                    <label>Motif</label>
                    <input type="text" wire:model="bookReason" placeholder="Ex : consultation de suivi, douleur...">
                    @error('bookReason') <div class="pp-error">{{ $message }}</div> @enderror
                </div>
                <div style="font-size:11.5px;color:var(--ink-faint);margin-bottom:10px;">
                    C'est une demande — la clinique la confirmera ou te proposera un autre créneau si besoin.
                </div>
                <button type="submit" class="pp-btn">Envoyer la demande</button>
                <button type="button" class="pp-btn ghost" wire:click="closeBook" style="margin-top:8px;">Annuler</button>
            </form>
        </div>
    </div>

    {{-- ===== Modale Proposer une autre date ===== --}}
    <div class="pp-modal-backdrop {{ $showAltModal ? 'active' : '' }}" wire:click.self="closeProposeAlt">
        <div class="pp-modal">
            <div class="pp-modal-head">
                <h3 class="serif">Proposer une autre date</h3>
                <button class="pp-modal-close" wire:click="closeProposeAlt">✕</button>
            </div>
            <form wire:submit="saveProposeAlt">
                <div class="pp-row">
                    <div class="pp-field">
                        <label>Date qui t'arrange</label>
                        <input type="date" wire:model="altDate">
                        @error('altDate') <div class="pp-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="pp-field">
                        <label>Heure</label>
                        <input type="time" wire:model="altTime">
                        @error('altTime') <div class="pp-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div style="font-size:11.5px;color:var(--ink-faint);margin-bottom:10px;">
                    Ta proposition sera envoyée à la clinique, qui la confirmera selon la disponibilité du médecin.
                </div>
                <button type="submit" class="pp-btn">Envoyer ma proposition</button>
                <button type="button" class="pp-btn ghost" wire:click="closeProposeAlt" style="margin-top:8px;">Annuler</button>
            </form>
        </div>
    </div>
</div>
