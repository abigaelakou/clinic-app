<div>
    <div class="page-head">
        <div>
            @php
                $hr = now()->hour;
                $ge = $hr < 12 ? '☀️' : ($hr < 18 ? '🌤️' : '🌙');
            @endphp
            <h1><span class="greet-emoji">{{ $ge }}</span> Rapports & pilotage</h1>
            <div class="date">Vue d'ensemble de l'activité de la clinique</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <div class="view-toggle">
                <button class="{{ $period === 'month' ? 'active' : '' }}" wire:click="setPeriod('month')">Ce mois-ci</button>
                <button class="{{ $period === 'year' ? 'active' : '' }}" wire:click="setPeriod('year')">Cette année</button>
                <button class="{{ $period === 'all' ? 'active' : '' }}" wire:click="setPeriod('all')">Depuis toujours</button>
            </div>
            <a href="{{ route('reports.export.excel', ['period' => $period, 'doctor' => $filterDoctorId, 'domain' => $filterStockDomain]) }}" class="btn ghost">⬇ Excel</a>
            <a href="{{ route('reports.export.pdf', ['period' => $period, 'doctor' => $filterDoctorId, 'domain' => $filterStockDomain]) }}" class="btn ghost">⬇ PDF</a>
        </div>
    </div>

    <div class="card" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:14px 18px;margin-bottom:18px;">
        <div style="font-size:12.5px;font-weight:600;color:var(--ink-soft);">Filtrer :</div>

        <select wire:model.live="filterDoctorId" style="padding:8px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;max-width:240px;">
            <option value="">— Tous les médecins —</option>
            @foreach($filteredDoctors as $doc)
                <option value="{{ $doc->id }}">{{ $doc->user->name ?? '' }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStockDomain" style="padding:8px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
            <option value="">Tous les domaines de stock</option>
            <option value="pharmacie">Pharmacie</option>
            <option value="consommable">Consommables</option>
            <option value="non_consommable">Non consommables</option>
            <option value="cuisine">Cuisine</option>
        </select>
    </div>

    @php
        $deltaBadge = function($value, $lowerIsBetter = false) {
            if ($value === null) return '';
            $isGood = $lowerIsBetter ? $value < 0 : $value > 0;
            $isBad = $lowerIsBetter ? $value > 0 : $value < 0;
            $color = $isGood ? 'var(--ok)' : ($isBad ? 'var(--crit)' : 'var(--ink-faint)');
            $arrow = $value > 0 ? '▲' : ($value < 0 ? '▼' : '—');
            return '<span style="font-size:11px;font-weight:700;color:'.$color.';margin-left:6px;">'.$arrow.' '.abs($value).'%</span>';
        };
    @endphp

    <div class="kpi-row">
        <div class="kpi">
            <div class="label">Nouvelles patientes</div>
            <div class="value">{{ $newPatients }} {!! $deltaBadge($deltas['newPatients']) !!}</div>
        </div>
        <div class="kpi">
            <div class="label">Consultations</div>
            <div class="value">{{ $consultationsTotal }} {!! $deltaBadge($deltas['consultationsTotal']) !!}</div>
        </div>
        <div class="kpi">
            <div class="label">RDV confirmés / total</div>
            <div class="value">{{ $apptConfirmed }} <span style="font-size:13px;color:var(--ink-faint);">/ {{ $apptTotal }}</span> {!! $deltaBadge($deltas['apptConfirmed']) !!}</div>
        </div>
        <div class="kpi">
            <div class="label">Taux d'annulation</div>
            <div class="value" style="color:{{ $cancelRate > 20 ? 'var(--crit)' : 'var(--ink)' }};">{{ $cancelRate }}% {!! $deltaBadge($deltas['cancelRate'], true) !!}</div>
        </div>
    </div>

    @if($period !== 'all')
        <div style="font-size:11px;color:var(--ink-faint);margin:-10px 0 18px;">Comparé à {{ $period === 'month' ? 'le mois précédent' : "l'année précédente" }}.</div>
    @endif

    <div class="kpi-row">
        <div class="kpi">
            <div class="label">Produits en rupture</div>
            <div class="value" style="color:var(--crit);">{{ $ruptureCount }}</div>
        </div>
        <div class="kpi">
            <div class="label">Produits en alerte</div>
            <div class="value" style="color:var(--warn);">{{ $lowStockCount }}</div>
        </div>
        <div class="kpi">
            <div class="label">Entrées de stock</div>
            <div class="value" style="color:var(--ok);">+{{ $entries }}</div>
        </div>
        <div class="kpi">
            <div class="label">Sorties de stock</div>
            <div class="value">−{{ $exits }}</div>
        </div>
    </div>

    <div class="card" style="margin-bottom:18px;">
        <div class="card-head" style="padding:0 0 14px;"><h2><span class="card-icon i-lavender">📈</span>Évolution des consultations — 6 derniers mois</h2></div>
        <div style="display:flex;align-items:flex-end;gap:10px;height:120px;padding:0 6px;">
            @foreach($evolution as $point)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;">
                    <div style="font-size:11px;font-weight:700;color:var(--ink-soft);margin-bottom:4px;">{{ $point['total'] }}</div>
                    <div style="width:100%;max-width:38px;border-radius:6px 6px 0 0;background:linear-gradient(180deg,var(--peach),var(--clay));height:{{ max(4, round($point['total'] / $maxEvolution * 90)) }}px;"></div>
                    <div style="font-size:10.5px;color:var(--ink-faint);margin-top:6px;text-transform:capitalize;">{{ $point['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid-2">
        <div class="card domain-card">
            <div class="card-head" style="padding:0 0 10px;"><h2><span class="card-icon i-mint">🩺</span>Consultations par type</h2></div>
            @forelse($byType as $row)
                <div class="domain-row">
                    <div class="dname">
                        <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:18px;padding:0 5px;border-radius:5px;font-size:9.5px;font-weight:800;color:#fff;background:{{ $row->type->color ?? 'var(--clay)' }};margin-right:6px;">{{ $row->type->code ?? '?' }}</span>
                        {{ $row->type->label ?? 'Type supprimé' }}
                    </div>
                    <div class="domain-track"><div class="domain-fill" style="width:{{ round($row->total / $maxTypeCount * 100) }}%;background:{{ $row->type->color ?? 'var(--clay)' }};"></div></div>
                    <div class="domain-pct">{{ $row->total }}</div>
                </div>
            @empty
                <div style="font-size:12.5px;color:var(--ink-soft);padding:10px 0;">Aucune consultation typée sur cette période.</div>
            @endforelse
            @if($noTypeCount > 0)
                <div style="font-size:11.5px;color:var(--ink-faint);margin-top:10px;">+ {{ $noTypeCount }} consultation(s) sans type renseigné.</div>
            @endif
        </div>

        <div class="card domain-card">
            <div class="card-head" style="padding:0 0 10px;"><h2><span class="card-icon i-sky">👥</span>Consultations par médecin</h2></div>
            @if($filterDoctorId)
                <div style="font-size:12.5px;color:var(--ink-soft);padding:10px 0;">Filtré sur un seul médecin — retire le filtre pour voir la répartition.</div>
            @else
                @forelse($byDoctor as $row)
                    <div class="domain-row">
                        <div class="dname">{{ $row->doctor->user->name ?? 'Médecin supprimé' }}</div>
                        <div class="domain-track"><div class="domain-fill" style="width:{{ round($row->total / $maxDoctorCount * 100) }}%;background:var(--sky);"></div></div>
                        <div class="domain-pct">{{ $row->total }}</div>
                    </div>
                @empty
                    <div style="font-size:12.5px;color:var(--ink-soft);padding:10px 0;">Aucune consultation sur cette période.</div>
                @endforelse
            @endif
        </div>
    </div>
</div>
