<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 26px 30px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #241A15; font-size: 11px; }
    .header { display: table; width: 100%; margin-bottom: 18px; }
    .header-logo { display: table-cell; width: 54px; vertical-align: middle; }
    .header-logo img { width: 44px; height: 44px; }
    .header-text { display: table-cell; vertical-align: middle; padding-left: 12px; }
    .clinic-name { font-size: 17px; font-weight: bold; color: #241A15; margin: 0; }
    .clinic-sub { font-size: 10px; color: #5B4F49; margin: 2px 0 0; }
    .title-bar { background: #241A15; color: #fff; padding: 8px 12px; border-radius: 4px; margin-bottom: 4px; }
    .meta { font-size: 9.5px; color: #8C7C73; margin-bottom: 16px; }
    .summary { display: table; width: 100%; margin-bottom: 18px; }
    .summary-item {
        display: table-cell; text-align: center; border: 1px solid #E7E3DD; border-radius: 4px;
        padding: 10px 4px;
    }
    .summary-item .num { font-size: 18px; font-weight: bold; }
    .summary-item .lbl { font-size: 8.5px; color: #5B4F49; text-transform: uppercase; }
    h3 { font-size: 12.5px; margin: 18px 0 8px; }
    table { width: 100%; border-collapse: collapse; }
    thead th {
        background: #F3F5F2; color: #5B4F49; text-transform: uppercase; font-size: 9px;
        text-align: left; padding: 6px 8px; border-bottom: 1.5px solid #E7E3DD;
    }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #E7E3DD; font-size: 10.5px; }
    .footer { margin-top: 22px; font-size: 9px; color: #A79A92; text-align: center; }
</style>
</head>
<body>

    <div class="header" style="border-left:4px solid #C0410C;padding-left:14px;">
        <div class="header-text">
            <p class="clinic-name">CLINIQUE FAME</p>
            <p class="clinic-sub">Rapport d'activité</p>
        </div>
    </div>

    <div class="title-bar">
        <span style="font-weight:bold;">{{ $periodLabel }}</span>
    </div>
    <div class="meta">
        Exporté le {{ now()->translatedFormat('l j F Y à H:i') }}
        @if($doctorName) · Médecin : {{ $doctorName }} @endif
        @if($domainLabel) · Domaine : {{ $domainLabel }} @endif
    </div>

    <div class="summary">
        <div class="summary-item"><div class="num">{{ $newPatients }}</div><div class="lbl">Nvlles patientes</div></div>
        <div class="summary-item"><div class="num">{{ $consultationsTotal }}</div><div class="lbl">Consultations</div></div>
        <div class="summary-item"><div class="num">{{ $apptConfirmed }}/{{ $apptTotal }}</div><div class="lbl">RDV confirmés</div></div>
        <div class="summary-item"><div class="num" style="color:{{ $cancelRate > 20 ? '#B3261E' : '#241A15' }};">{{ $cancelRate }}%</div><div class="lbl">Annulation</div></div>
        <div class="summary-item"><div class="num" style="color:#B3261E;">{{ $ruptureCount }}</div><div class="lbl">Rupture stock</div></div>
        <div class="summary-item"><div class="num" style="color:#C98A1A;">{{ $lowStockCount }}</div><div class="lbl">Alerte stock</div></div>
    </div>

    <h3>Consultations par type</h3>
    <table>
        <thead><tr><th>Code</th><th>Type</th><th>Total</th></tr></thead>
        <tbody>
            @forelse($byType as $row)
                <tr>
                    <td>{{ $row->type->code ?? '?' }}</td>
                    <td>{{ $row->type->label ?? 'Type supprimé' }}</td>
                    <td>{{ $row->total }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Aucune consultation typée sur cette période.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($byDoctor->isNotEmpty())
        <h3>Consultations par médecin</h3>
        <table>
            <thead><tr><th>Médecin</th><th>Total</th></tr></thead>
            <tbody>
                @foreach($byDoctor as $row)
                    <tr>
                        <td>{{ $row->doctor->user->name ?? 'Médecin supprimé' }}</td>
                        <td>{{ $row->total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">CLINIQUE FAME · Rapport généré automatiquement</div>

</body>
</html>
