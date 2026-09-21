<div>
    <div class="page-head">
        <div>
            <h1>Rendez-vous</h1>
            <div class="date">{{ $currentDay->translatedFormat('l j F Y') }}</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <div class="view-toggle">
                <button class="{{ $viewMode === 'day' ? 'active' : '' }}" wire:click="setViewMode('day')">Jour</button>
                <button class="{{ $viewMode === 'week' ? 'active' : '' }}" wire:click="setViewMode('week')">Semaine</button>
            </div>
            @if($canSignalUnavailable)
                <button class="btn ghost" wire:click="openUnavailable">Signaler une indisponibilité</button>
            @endif
            @if($canCreate)
                <button class="btn" wire:click="$dispatch('open-new-appointment-modal')">+ Nouveau rendez-vous</button>
            @endif
        </div>
    </div>

    <div class="day-strip">
        @foreach($weekDays as $day)
            <div class="day-chip {{ $day->isSameDay($currentDay) ? 'today' : '' }} {{ in_array($day->toDateString(), $unavailableDatesInWeek) ? 'unavailable' : '' }}" wire:click="selectDay('{{ $day->toDateString() }}')">
                {{ $day->translatedFormat('D') }}<br><b>{{ $day->format('d') }}</b>
            </div>
        @endforeach
    </div>

    @if($viewMode === 'week')
        <div class="week-grid">
            @foreach($weekDays as $day)
                <div class="week-col {{ $day->isToday() ? 'today-col' : '' }} {{ in_array($day->toDateString(), $unavailableDatesInWeek) ? 'unavailable-col' : '' }}">
                    <div class="week-col-head">
                        <div class="wd">{{ $day->translatedFormat('D') }}</div>
                        <div class="wn">{{ $day->format('d') }}</div>
                    </div>
                    @if(in_array($day->toDateString(), $unavailableDatesInWeek))
                        <div class="week-col-unavail-tag">Indisponible</div>
                    @endif
                    <div class="week-col-body">
                        @forelse($weekAppointments[$day->toDateString()] ?? [] as $appt)
                            <div class="week-appt {{ $appt->status === 'pending' ? 'pending' : '' }}" wire:click="selectDay('{{ $day->toDateString() }}'); setViewMode('day')">
                                <div class="week-appt-time">{{ $appt->scheduled_at->format('H:i') }}</div>
                                <div class="week-appt-name">{{ $appt->patient->first_name ?? '' }}</div>
                            </div>
                        @empty
                            <div class="week-col-empty">—</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($myUnavailabilityToday)
        <div class="unavailable-banner">
            ⚠ Tu as indiqué être indisponible ce jour-là ({{ $myUnavailabilityToday->start_time }}–{{ $myUnavailabilityToday->end_time }}) — motif : {{ $myUnavailabilityToday->reason }}
        </div>
    @endif

    @if($othersUnavailableToday->isNotEmpty())
        <div class="unavailable-banner" style="flex-direction:column;align-items:flex-start;gap:4px;">
            <div>⚠ {{ $othersUnavailableToday->count() }} médecin(s) indisponible(s) aujourd'hui :</div>
            @foreach($othersUnavailableToday as $ua)
                <div style="font-weight:400;font-size:12px;">
                    {{ $ua->doctor->user->name ?? '—' }} · {{ $ua->start_time }}–{{ $ua->end_time }} · {{ $ua->reason }}
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid-2" style="{{ $viewMode === 'week' ? 'display:none;' : '' }}">
        <div class="card">
            <div class="card-head"><h2><span class="card-icon i-peach">📅</span>{{ $currentDay->isToday() ? "Aujourd'hui" : $currentDay->translatedFormat('l j F') }}</h2><span class="see-all">{{ $todayCount }} rendez-vous</span></div>
            <div class="agenda">
                @forelse($today as $appt)
                    <div class="agenda-row">
                        <div class="agenda-time">{{ $appt->scheduled_at->format('H:i') }}</div>
                        <div class="agenda-card {{ in_array($appt->status, ['pending', 'rescheduled']) ? 'pending' : ($appt->status === 'completed' ? 'completed' : 'confirmed') }}" style="{{ $appt->status === 'cancelled' ? 'opacity:.5;' : '' }}">
                            <div class="agenda-doc">{{ $appt->doctor->user->name ?? '' }}</div>
                            <div class="agenda-patient">{{ $appt->patient->first_name }} — {{ $appt->reason }}</div>
                            @if($appt->type === 'teleconsultation')
                                <div class="agenda-tag tele">🎥 Vidéo</div>
                            @elseif($appt->status === 'pending')
                                <div class="agenda-tag">En attente de confirmation</div>
                            @elseif($appt->status === 'rescheduled')
                                <div class="agenda-tag" style="color:var(--warn);">Nouvelle date proposée — en attente de la patiente</div>
                            @elseif($appt->status === 'cancelled')
                                <div class="agenda-tag" style="color:var(--crit);">Annulé</div>
                            @elseif($appt->status === 'completed')
                                <div class="agenda-tag" style="color:var(--ink-faint);">✓ Terminé</div>
                            @endif
                            @if(! in_array($appt->status, ['cancelled', 'completed']) && $canConfirm)
                                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;">
                                    @if(in_array($appt->status, ['pending', 'rescheduled']))
                                        <button class="btn ghost" style="padding:5px 9px;font-size:11px;color:var(--ok);" wire:click="confirm({{ $appt->id }})">✓ Confirmer</button>
                                    @endif
                                    <button class="btn ghost" style="padding:5px 9px;font-size:11px;color:var(--ok);" wire:click="markCompleted({{ $appt->id }})">✓ Terminé</button>
                                    <button class="btn ghost" style="padding:5px 9px;font-size:11px;" wire:click="openReschedule({{ $appt->id }})">Reporter</button>
                                    <button class="btn ghost" style="padding:5px 9px;font-size:11px;color:var(--crit);" wire:click="openCancel({{ $appt->id }})">Annuler</button>
                                    @if($canDelete)
                                        <button class="btn ghost" style="padding:5px 9px;font-size:11px;color:var(--crit);"
                                                wire:click="askConfirmDelete({{ $appt->id }})">🗑</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="agenda-row">Aucun rendez-vous ce jour-là.</div>
                @endforelse
            </div>
            @if($today->hasPages())
                <div class="pager">
                    <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ $today->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage('dayPage')">← Préc.</button>
                    <span class="pg-info">Page {{ $today->currentPage() }}</span>
                    <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ ! $today->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage('dayPage')">Suiv. →</button>
                </div>
            @endif
        </div>

        <div>
            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><h2><span class="card-icon i-gold">⏳</span>Demandes en attente</h2><span class="see-all">{{ $pending->count() }}</span></div>
                @forelse($pending as $appt)
                    <div class="req-item">
                        <div class="req-avatar">{{ strtoupper(substr($appt->patient->first_name ?? '?', 0, 1)) }}</div>
                        <div class="req-info">
                            <div class="req-name">{{ $appt->patient->first_name }}</div>
                            <div class="req-meta">Souhaite le {{ $appt->scheduled_at->format('d/m') }}, {{ $appt->scheduled_at->format('H:i') }}</div>
                        </div>
                    </div>
                    @if($canConfirm)
                        <div class="req-actions">
                            <button class="btn" style="flex:1;padding:8px;" wire:click="confirm({{ $appt->id }})">Confirmer</button>
                            <button class="btn ghost" style="flex:1;padding:8px;" wire:click="openReschedule({{ $appt->id }})">Proposer autre créneau</button>
                        </div>
                    @endif
                @empty
                    <div class="req-item"><div class="req-info">Aucune demande en attente.</div></div>
                @endforelse
                @if($pending->hasPages())
                    <div class="pager">
                        <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ $pending->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage('pendingPage')">← Préc.</button>
                        <span class="pg-info">Page {{ $pending->currentPage() }}</span>
                        <button class="btn ghost" style="padding:5px 10px;font-size:11px;{{ ! $pending->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage('pendingPage')">Suiv. →</button>
                    </div>
                @endif
            </div>

            <div class="card domain-card">
                <div class="card-head" style="padding:0 0 10px;"><h2><span class="card-icon i-lavender">👥</span>Rendez-vous par médecin</h2></div>
                @forelse($byDoctor as $doctorName => $appointments)
                    <div class="domain-row">
                        <div class="dname">{{ $doctorName }}</div>
                        <div class="domain-track"><div class="domain-fill" style="width:{{ min(100, $appointments->count() * 20) }}%;background:var(--clay);"></div></div>
                        <div class="domain-pct">{{ $appointments->count() }}</div>
                    </div>
                @empty
                    <div style="font-size:12.5px;color:var(--ink-soft);">Rien à afficher pour l'instant.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== Modale Annulation ===== --}}
    <div class="modal-backdrop {{ $showCancelModal ? 'active' : '' }}" wire:click.self="closeCancel">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Annuler le rendez-vous</h3>
                <button class="modal-close" wire:click="closeCancel">✕</button>
            </div>
            <form wire:submit="saveCancel">
                <div class="form-field">
                    <label>Motif de l'annulation</label>
                    <input type="text" wire:model="cancelReason" placeholder="Ex : patiente indisponible, urgence...">
                    @error('cancelReason') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeCancel">Retour</button>
                    <button type="submit" class="btn" style="background:var(--crit);">Confirmer l'annulation</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Report ===== --}}
    <div class="modal-backdrop {{ $showRescheduleModal ? 'active' : '' }}" wire:click.self="closeReschedule">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Reporter le rendez-vous</h3>
                <button class="modal-close" wire:click="closeReschedule">✕</button>
            </div>
            <form wire:submit="saveReschedule">
                <div class="form-row">
                    <div class="form-field">
                        <label>Nouvelle date</label>
                        <input type="date" wire:model="rescheduleDate">
                        @error('rescheduleDate') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Nouvelle heure</label>
                        <input type="time" wire:model="rescheduleTime">
                        @error('rescheduleTime') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeReschedule">Annuler</button>
                    <button type="submit" class="btn">Confirmer le report</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Indisponibilité ===== --}}
    <div class="modal-backdrop {{ $showUnavailableModal ? 'active' : '' }}" wire:click.self="closeUnavailable">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Signaler une indisponibilité</h3>
                <button class="modal-close" wire:click="closeUnavailable">✕</button>
            </div>

            @if(count($conflictingAppointments) > 0)
                <div style="background:var(--warn-tint);border-radius:var(--radius-s);padding:12px 14px;margin-bottom:14px;">
                    <div style="font-size:12.5px;font-weight:700;color:var(--warn);margin-bottom:6px;">
                        ⚠ {{ count($conflictingAppointments) }} rendez-vous déjà pris sur cette période
                    </div>
                    @foreach($conflictingAppointments as $c)
                        <div style="font-size:12px;color:var(--ink-soft);">{{ $c['date'] }} à {{ $c['time'] }} — {{ $c['patient'] }}</div>
                    @endforeach
                    <div style="font-size:11.5px;color:var(--ink-faint);margin-top:8px;">
                        L'indisponibilité a été enregistrée quand même — pense à recontacter ces patientes toi-même
                        pour l'instant (pas encore de notification automatique).
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" wire:click="closeUnavailable">Fermer</button>
                </div>
            @else
                <form wire:submit="saveUnavailable">
                    <div class="form-row">
                        <div class="form-field">
                            <label>Du</label>
                            <input type="date" wire:model="unavailableStartDate">
                            @error('unavailableStartDate') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-field">
                            <label>Au</label>
                            <input type="date" wire:model="unavailableEndDate">
                            @error('unavailableEndDate') <div class="field-error">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="form-field">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" wire:model.live="unavailableAllDay" style="width:auto;">
                            Journée(s) complète(s)
                        </label>
                    </div>

                    @unless($unavailableAllDay)
                        <div class="form-row">
                            <div class="form-field">
                                <label>De</label>
                                <input type="time" wire:model="unavailableStart">
                            </div>
                            <div class="form-field">
                                <label>À</label>
                                <input type="time" wire:model="unavailableEnd">
                            </div>
                        </div>
                    @endunless

                    <div class="form-field">
                        <label>Motif</label>
                        <input type="text" wire:model="unavailableReason" placeholder="Ex : congé, formation, urgence personnelle...">
                        @error('unavailableReason') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn ghost" wire:click="closeUnavailable">Annuler</button>
                        <button type="submit" class="btn">Enregistrer</button>
                    </div>
                </form>
            @endif
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

    <livewire:new-appointment-modal />
</div>
