<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 26px 30px; }
    body { font-family: 'DejaVu Sans', sans-serif; color: #241A15; font-size: 11px; }
    .header { border-left: 4px solid #C0410C; padding-left: 14px; margin-bottom: 18px; }
    .clinic-name { font-size: 17px; font-weight: bold; margin: 0; }
    .clinic-sub { font-size: 10px; color: #5B4F49; margin: 2px 0 0; }
    .title-bar { background: #241A15; color: #fff; padding: 8px 12px; border-radius: 4px; margin-bottom: 4px; }
    .meta { font-size: 9.5px; color: #8C7C73; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead th {
        background: #F3F5F2; color: #5B4F49; text-transform: uppercase; font-size: 9px;
        text-align: left; padding: 7px 8px; border-bottom: 1.5px solid #E7E3DD;
    }
    tbody td { padding: 7px 8px; border-bottom: 1px solid #E7E3DD; font-size: 10.5px; }
    .suggest { color: #C0410C; font-weight: bold; }
    .footer { margin-top: 22px; font-size: 9px; color: #A79A92; text-align: center; }
</style>
</head>
<body>

    <div class="header">
        <p class="clinic-name">CLINIQUE FAME</p>
        <p class="clinic-sub">Liste de réapprovisionnement suggérée</p>
    </div>

    <div class="title-bar"><span style="font-weight:bold;">{{ $domainLabel }}</span></div>
    <div class="meta">Exporté le {{ now()->translatedFormat('l j F Y à H:i') }} — à utiliser comme base de commande fournisseur.</div>

    <table>
        <thead>
            <tr><th>Produit</th><th>Catégorie</th><th>En stock</th><th>Seuil</th><th>Suggéré</th></tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->category->name }}</td>
                    <td>{{ $p->formattedQuantity() }} {{ $p->unit }}</td>
                    <td>{{ rtrim(rtrim(number_format($p->alert_threshold, 2, '.', ''), '0'), '.') }}</td>
                    <td class="suggest">+ {{ rtrim(rtrim(number_format($p->suggestedReorderQty(), 2, '.', ''), '0'), '.') }} {{ $p->unit }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun produit à réapprovisionner sur ce domaine pour l'instant.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">CLINIQUE FAME · Document généré automatiquement</div>

</body>
</html>
