<div>
    <div class="page-head">
        <div>
            <h1>Rendez-vous</h1>
            <div class="date">{{ now()->translatedFormat('l j F Y') }} · Vue journalière</div>
        </div>
        <div style="display:flex;gap:10px;">
            <button class="btn">+ Nouveau rendez-vous</button>
        </div>
    </div>

    @if (session('flash'))
        <div class="card" style="padding:12px 18px;margin-bottom:16px;border-left:3px solid var(--ok);">
            {{ session('flash') }}
        </div>
    @endif

    <div class="grid-2">
        <div class="card">
            <div class="card-head"><h2>Aujourd'hui</h2><span class="see-all">{{ $today->count() }} rendez-vous</span></div>
            <div class="agenda">
                @forelse($today as $appt)
                    <div class="agenda-row">
                        <div class="agenda-time">{{ $appt->scheduled_at->format('H:i') }}</div>
                        <div class="agenda-card {{ $appt->status === 'pending' ? 'pending' : 'confirmed' }}">
                            <div class="agenda-doc">{{ $appt->doctor->title ?? '' }} {{ $appt->doctor->user->name ?? '' }}</div>
                            <div class="agenda-patient">{{ $appt->patient->first_name }} — {{ $appt->reason }}</div>
                            @if($appt->type === 'teleconsultation')
                                <div class="agenda-tag tele">🎥 Vidéo</div>
                            @elseif($appt->status === 'pending')
                                <div class="agenda-tag">En attente de confirmation</div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="agenda-row">Aucun rendez-vous aujourd'hui.</div>
                @endforelse
            </div>
        </div>

        <div>
            <div class="card" style="margin-bottom:18px;">
                <div class="card-head"><h2>Demandes en attente</h2><span class="see-all">{{ $pending->count() }}</span></div>
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
                            <button class="btn ghost" style="flex:1;padding:8px;">Proposer autre créneau</button>
                        </div>
                    @endif
                @empty
                    <div class="req-item"><div class="req-info">Aucune demande en attente.</div></div>
                @endforelse
            </div>

            <div class="card domain-card">
                <div class="card-head" style="padding:0 0 10px;"><h2>Rendez-vous du jour par médecin</h2></div>
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
</div>
