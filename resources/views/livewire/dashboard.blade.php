<div>
    <div class="page-head">
        <svg class="head-arc" viewBox="0 0 180 90" fill="none">
            <path d="M10,85 A150,150 0 0,1 175,5" stroke="#C0410C" stroke-width="1.4" stroke-dasharray="1 7" stroke-linecap="round"/>
        </svg>
        <div>
            <h1>Bonjour, {{ auth()->user()?->name ? explode(' ', auth()->user()->name)[0] : 'invité' }}</h1>
            <div class="date">{{ now()->translatedFormat('l j F Y') }} · Vue d'ensemble de la clinique</div>
        </div>
        <div style="display:flex;gap:10px;">
            <button class="btn ghost">Exporter le rapport</button>
            <button class="btn">+ Nouveau rendez-vous</button>
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi">
            <div class="label">Alertes actives
                <div class="swatch" style="background:var(--crit-tint);color:var(--crit);">⚠</div>
            </div>
            <div class="value" data-count="{{ $alertsCount }}">{{ $alertsCount }}</div>
            <svg class="spark" width="100%" height="26" viewBox="0 0 120 26" preserveAspectRatio="none"><polyline points="0,20 20,18 40,14 60,16 80,8 100,10 120,4" fill="none" stroke="#B3261E" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.5"/></svg>
        </div>
        <div class="kpi">
            <div class="label">RDV aujourd'hui
                <div class="swatch" style="background:var(--info-tint);color:var(--info);">📅</div>
            </div>
            <div class="value" data-count="{{ $appointmentsToday }}">{{ $appointmentsToday }}</div>
            <div class="delta down">{{ $appointmentsPending }} en attente</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="card-head"><h2>Alertes de stock</h2><span class="see-all">Voir tout</span></div>
            <div class="card-sub">Produits sous le seuil ou en rupture</div>
            @forelse($stockAlerts as $product)
                <div class="stock-row">
                    <div class="bar {{ $product->status() === 'rupture' ? 'crit' : 'warn' }}"></div>
                    <div class="stock-info">
                        <div class="stock-name">{{ $product->name }}</div>
                        <div class="stock-meta">{{ $product->category->name ?? '' }}</div>
                    </div>
                    <div class="pill {{ $product->status() === 'rupture' ? 'crit' : 'warn' }}">
                        {{ $product->status() === 'rupture' ? 'Rupture' : $product->quantity_on_hand.' / seuil '.$product->alert_threshold }}
                    </div>
                </div>
            @empty
                <div class="stock-row">Aucune alerte pour le moment 🎉</div>
            @endforelse
        </div>

        <div class="card">
            <div class="card-head"><h2>Rendez-vous du jour</h2><span class="see-all">Agenda</span></div>
            @forelse($todayAppointments as $appt)
                <div class="rdv-row">
                    <div class="rdv-time">{{ $appt->scheduled_at->format('H:i') }}</div>
                    <div class="rdv-dot {{ $appt->status }}"></div>
                    <div class="rdv-info">
                        <div class="rdv-patient">{{ $appt->patient->first_name }}</div>
                        <div class="rdv-meta">{{ $appt->doctor->title ?? '' }} {{ $appt->doctor->user->name ?? '' }}</div>
                    </div>
                    <div class="rdv-status {{ $appt->status }}">{{ ucfirst($appt->status) }}</div>
                </div>
            @empty
                <div class="rdv-row">Aucun rendez-vous aujourd'hui</div>
            @endforelse
        </div>
    </div>

    <div class="footnote">CLINIQUE FAME · Application de gestion intégrée</div>
</div>
