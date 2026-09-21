<div>
    <div class="page-head">
        <svg class="head-arc" viewBox="0 0 180 90" fill="none">
            <path d="M10,85 A150,150 0 0,1 175,5" stroke="#C0410C" stroke-width="1.4" stroke-dasharray="1 7" stroke-linecap="round"/>
        </svg>
        <div>
            @php
                $hr = now()->hour;
                $ge = $hr < 12 ? '☀️' : ($hr < 18 ? '🌤️' : '🌙');
                $gw = $hr < 12 ? 'Bonjour' : ($hr < 18 ? 'Bon après-midi' : 'Bonsoir');
            @endphp
            <h1><span class="greet-emoji">{{ $ge }}</span> {{ $gw }}, {{ auth()->user()?->name ? explode(' ', auth()->user()->name)[0] : 'invité' }}</h1>
            <div class="date">{{ now()->translatedFormat('l j F Y') }} · Vue d'ensemble de la clinique</div>
        </div>
        <div style="display:flex;gap:10px;">
            <button class="btn ghost">Exporter le rapport</button>
            @if($canSeeAppointments)
                <button class="btn" wire:click="$dispatch('open-new-appointment-modal')">+ Nouveau rendez-vous</button>
            @endif
        </div>
    </div>

    <div class="kpi-row">
        @if($canSeeStock)
        <div class="kpi">
            <div class="label">Alertes actives
                <div class="swatch" style="background:var(--crit-tint);color:var(--crit);">⚠</div>
            </div>
            <div class="value" data-count="{{ $alertsCount }}">{{ $alertsCount }}</div>
        </div>
        @endif
        @if($canSeeAppointments)
        <div class="kpi">
            <div class="label">RDV aujourd'hui
                <div class="swatch" style="background:var(--info-tint);color:var(--info);">📅</div>
            </div>
            <div class="value" data-count="{{ $appointmentsToday }}">{{ $appointmentsToday }}</div>
            <div class="delta down">{{ $appointmentsPending }} en attente</div>
        </div>
        @endif
    </div>

    <div class="grid-2">
        @if($canSeeStock)
        <div class="card">
            <div class="card-head"><h2><span class="card-icon i-peach">💊</span>Alertes de stock</h2>@if($canSeeStock)<a href="{{ route('stocks') }}" class="see-all">Voir tout</a>@endif</div>
            <div class="card-sub">Produits sous le seuil ou en rupture — domaines que tu gères</div>
            @forelse($stockAlerts as $product)
                <div class="stock-row">
                    <div class="bar {{ $product->status() === 'rupture' ? 'crit' : 'warn' }}"></div>
                    <div class="stock-info">
                        <div class="stock-name">{{ $product->name }}</div>
                        <div class="stock-meta">{{ $product->category->name ?? '' }}</div>
                    </div>
                    <div class="pill {{ $product->status() === 'rupture' ? 'crit' : 'warn' }}">
                        {{ $product->status() === 'rupture' ? 'Rupture' : $product->formattedQuantity().' / seuil '.rtrim(rtrim(number_format($product->alert_threshold, 2, '.', ''), '0'), '.') }}
                    </div>
                </div>
            @empty
                <div class="stock-row">Aucune alerte pour le moment 🎉</div>
            @endforelse
        </div>
        @endif

        @if($canSeeAppointments)
        <div class="card">
            <div class="card-head"><h2><span class="card-icon i-sky">📅</span>Rendez-vous du jour</h2>@if($canSeeAppointments)<a href="{{ route('rdv') }}" class="see-all">Agenda</a>@endif</div>
            @forelse($todayAppointments as $appt)
                <div class="rdv-row">
                    <div class="rdv-time">{{ $appt->scheduled_at->format('H:i') }}</div>
                    <div class="rdv-dot {{ $appt->status }}"></div>
                    <div class="rdv-info">
                        <div class="rdv-patient">{{ $appt->patient->first_name }}</div>
                        <div class="rdv-meta">{{ $appt->doctor->user->name ?? '' }}</div>
                    </div>
                    <div class="rdv-status {{ $appt->status }}">{{ $appt->statusLabel() }}</div>
                </div>
            @empty
                <div class="rdv-row">Aucun rendez-vous aujourd'hui</div>
            @endforelse
        </div>
        @endif
    </div>

    @if(! $canSeeStock && ! $canSeeAppointments)
        <div class="card" style="padding:24px;text-align:center;color:var(--ink-soft);font-size:13px;">
            Ton rôle n'a pas de tableau de bord dédié pour l'instant — reviens vers ton responsable si tu penses
            que c'est une erreur.
        </div>
    @endif

    <div class="footnote">CLINIQUE FAME · Application de gestion intégrée</div>

    <livewire:new-appointment-modal />
</div>
