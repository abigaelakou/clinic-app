<div>
    <div class="page-head">
        <div>
            <h1>Gestion des stocks</h1>
            <div class="date">{{ $currentLabel }}</div>
        </div>
        @if($canWrite)
            <div style="display:flex;gap:10px;">
                <button class="btn">+ Nouvelle entrée</button>
            </div>
        @endif
    </div>

    <div class="domain-tabs">
        @foreach($domainTabs as $tab)
            <div class="dtab {{ $tab['key'] === $domain ? 'active' : '' }}" wire:click="selectDomain('{{ $tab['key'] }}')">
                {{ $tab['label'] }}
            </div>
        @endforeach
    </div>

    <div class="kpi-row">
        <div class="kpi" style="animation:none;"><div class="label">Produits référencés</div><div class="value">{{ $total }}</div></div>
        <div class="kpi" style="animation:none;"><div class="label">Stock bas</div><div class="value" style="color:var(--warn)">{{ $lowStockCount }}</div></div>
        <div class="kpi" style="animation:none;"><div class="label">En rupture</div><div class="value" style="color:var(--crit)">{{ $ruptureCount }}</div></div>
    </div>

    <div class="grid-2" style="align-items:flex-start;">
        <div style="flex:1 1 220px; max-width:240px;">
            <div class="card">
                <div class="card-head" style="padding:16px 18px 10px;"><h2 style="font-size:14.5px;">Catégories</h2></div>
                <div class="cat-item {{ ! $categoryId ? 'active' : '' }}" wire:click="selectCategory(null)">
                    <span>Toutes</span><span class="cat-count">{{ $categories->sum('products_count') }}</span>
                </div>
                @foreach($categories as $cat)
                    <div class="cat-item {{ $categoryId === $cat->id ? 'active' : '' }}" wire:click="selectCategory({{ $cat->id }})">
                        <span>{{ $cat->name }}</span><span class="cat-count">{{ $cat->products_count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div style="flex:3 1 500px;">
            <div class="card">
                <div class="card-head"><h2>{{ $currentLabel }}</h2><span class="see-all">Exporter</span></div>
                <div class="table-wrap">
                <table>
                    <thead><tr><th>Produit</th><th>Quantité</th><th>Seuil</th><th>Statut</th>@if($canWrite)<th></th>@endif</tr></thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td><div class="prod-name">{{ $product->name }}</div><div class="prod-cat">{{ $product->category->name }}</div></td>
                                <td>{{ $product->quantity_on_hand }}</td>
                                <td>{{ $product->alert_threshold }}</td>
                                <td>
                                    @if($product->status() === 'rupture')
                                        <span class="status-dot crit">Rupture</span>
                                    @elseif($product->status() === 'alerte')
                                        <span class="status-dot warn">Stock bas</span>
                                    @else
                                        <span class="status-dot ok">Disponible</span>
                                    @endif
                                </td>
                                @if($canWrite)
                                    <td><button class="btn ghost" style="padding:6px 10px;font-size:11.5px;">Mouvement</button></td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="5" style="text-align:center;color:var(--ink-soft);">Aucun produit dans cette catégorie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
