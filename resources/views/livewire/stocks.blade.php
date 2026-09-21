<div>
    <div class="page-head">
        <div>
            <h1>Gestion des stocks</h1>
            <div class="date">{{ $currentLabel }}</div>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('stocks.export.excel', ['domain' => $domain]) }}" class="btn ghost">⬇ Excel</a>
            <a href="{{ route('stocks.export.pdf', ['domain' => $domain]) }}" class="btn ghost">⬇ PDF</a>
            @if($canWrite)
                <button class="btn" wire:click="openNewProduct">+ Nouveau produit</button>
            @endif
        </div>
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
                <div class="card-head"><h2>{{ $currentLabel }}</h2></div>
                <div class="table-wrap">
                <table>
                    <thead><tr><th>Produit</th><th>Quantité</th><th>Seuil</th><th>Péremption</th><th>Statut</th><th></th></tr></thead>
                    <tbody>
                        @forelse($products as $product)
                            @php $expiry = $product->batches->first(); @endphp
                            <tr>
                                <td><div class="prod-name">{{ $product->name }}</div><div class="prod-cat">{{ $product->category->name }}</div></td>
                                <td>{{ $product->formattedQuantity() }} <span style="color:var(--ink-faint);font-size:11px;">{{ $product->unit }}</span></td>
                                <td>{{ rtrim(rtrim(number_format($product->alert_threshold, 2, '.', ''), '0'), '.') }}</td>
                                <td>
                                    @if($expiry)
                                        <span style="font-size:12px; {{ \Carbon\Carbon::parse($expiry->expiry_date)->lt(now()->addDays(30)) ? 'color:var(--warn);font-weight:700;' : 'color:var(--ink-soft);' }}">
                                            {{ \Carbon\Carbon::parse($expiry->expiry_date)->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span style="font-size:12px;color:var(--ink-faint);">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->status() === 'rupture')
                                        <span class="status-dot crit">Rupture</span>
                                    @elseif($product->status() === 'alerte')
                                        <span class="status-dot warn">Stock bas</span>
                                    @else
                                        <span class="status-dot ok">Disponible</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap;">
                                    <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;" wire:click="openHistory({{ $product->id }})">Historique</button>
                                    @if($canWrite)
                                        <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;" wire:click="openMovement({{ $product->id }})">Mouvement</button>
                                        <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;" wire:click="openEditProduct({{ $product->id }})">Modifier</button>
                                        <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;color:var(--crit);"
                                                wire:click="deleteProduct({{ $product->id }})"
                                                onclick="return confirm('Retirer {{ $product->name }} du stock ?')">Suppr.</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;color:var(--ink-soft);">Aucun produit dans cette catégorie.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($products->hasPages())
                    <div class="pager">
                        <button class="btn ghost" style="padding:6px 12px;font-size:11.5px;{{ $products->onFirstPage() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="previousPage">← Précédent</button>
                        <span class="pg-info">Page {{ $products->currentPage() }}</span>
                        <button class="btn ghost" style="padding:6px 12px;font-size:11.5px;{{ ! $products->hasMorePages() ? 'opacity:0.4;pointer-events:none;' : '' }}" wire:click="nextPage">Suivant →</button>
                    </div>
                @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Modale Mouvement ===== --}}
    <div class="modal-backdrop {{ $showMovementModal ? 'active' : '' }}" wire:click.self="closeMovement">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Mouvement — {{ $movementProductName }}</h3>
                <button class="modal-close" wire:click="closeMovement">✕</button>
            </div>
            <form wire:submit="saveMovement">
                <div class="form-field">
                    <label>Type de mouvement</label>
                    <div class="type-toggle">
                        <input type="radio" id="type-entry" value="entry" wire:model.live="movementType">
                        <label for="type-entry">+ Entrée</label>
                        <input type="radio" id="type-exit" value="exit" wire:model.live="movementType">
                        <label for="type-exit">− Sortie</label>
                    </div>
                </div>
                <div class="form-field">
                    <label>Quantité</label>
                    <input type="number" step="1" min="1" wire:model="movementQuantity">
                    @error('movementQuantity') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                @if($movementType === 'entry')
                    <div class="form-row">
                        <div class="form-field">
                            <label>N° de lot (optionnel)</label>
                            <input type="text" wire:model="movementLotNumber" placeholder="Ex : L-4471">
                        </div>
                        <div class="form-field">
                            <label>Date de péremption</label>
                            <input type="date" wire:model="movementExpiryDate">
                        </div>
                    </div>
                @endif

                @if($movementType === 'exit' && $canLinkPatient)
                    <div class="form-field">
                        <label>Patiente concernée (optionnel — dispensation nominative)</label>
                        @if($movementPatientId)
                            <div class="selected-chip">✓ {{ $selectedPatientName }} <span wire:click="clearMovementPatient">✕</span></div>
                        @else
                            <div class="combo" x-data="{ open:false }" @click.outside="open=false">
                                <div class="combo-trigger placeholder" @click="open = !open; if(open){ $nextTick(() => $refs.movPatientInput.focus()) }">
                                    <span>— Aucune (sortie générale) —</span>
                                    <span class="combo-arrow" :class="{ rot: open }">▾</span>
                                </div>
                                <div class="combo-panel" x-show="open" x-cloak style="display:none;">
                                    <div class="combo-search">
                                        <input type="text" x-ref="movPatientInput" wire:model.live.debounce.150ms="patientSearch" placeholder="Rechercher une patiente…" autocomplete="off" @click.stop>
                                    </div>
                                    <div class="combo-list">
                                        @forelse($filteredPatients as $p)
                                            <div class="combo-option" x-on:click="open=false" wire:click="selectMovementPatient({{ $p->id }})">{{ $p->first_name }} {{ $p->last_name }}</div>
                                        @empty
                                            <div class="combo-empty">Aucune patiente trouvée.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="form-field">
                    <label>Motif (optionnel)</label>
                    <input type="text" wire:model="movementReason" placeholder="Ex : réception fournisseur, dispensation...">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeMovement">Annuler</button>
                    <button type="submit" class="btn" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveMovement">Enregistrer</span>
                        <span wire:loading wire:target="saveMovement">Enregistrement…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Historique ===== --}}
    <div class="modal-backdrop {{ $showHistoryModal ? 'active' : '' }}" wire:click.self="closeHistory">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Historique — {{ $historyProductName }}</h3>
                <button class="modal-close" wire:click="closeHistory">✕</button>
            </div>
            <div style="max-height:400px;overflow-y:auto;">
                @forelse($movementHistory as $m)
                    <div class="consult-row" style="padding:12px 4px;">
                        <div class="consult-date">{{ $m->created_at->format('d/m/Y H:i') }}</div>
                        <div class="consult-info">
                            <b>{{ $m->type === 'entry' ? '+ Entrée' : '− Sortie' }} de {{ rtrim(rtrim(number_format($m->quantity, 2, '.', ''), '0'), '.') }}</b>
                            <div class="prod-cat">
                                Par {{ $m->user->name ?? '—' }}
                                @if($m->patient) · pour {{ $m->patient->first_name }} @endif
                                @if($m->reason) · {{ $m->reason }} @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding:20px;text-align:center;color:var(--ink-soft);font-size:13px;">Aucun mouvement enregistré pour ce produit.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== Modale Nouveau produit ===== --}}
    <div class="modal-backdrop {{ $showNewProductModal ? 'active' : '' }}" wire:click.self="closeNewProduct">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Nouveau produit — {{ $currentLabel }}</h3>
                <button class="modal-close" wire:click="closeNewProduct">✕</button>
            </div>
            <form wire:submit="saveNewProduct">
                <div class="form-field">
                    <label>Nom du produit</label>
                    <input type="text" wire:model="newProductName" placeholder="{{ $productPlaceholder }}">
                    @error('newProductName') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>Catégorie existante</label>
                    <select wire:model="newProductCategoryId" style="width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--line);background:var(--stone);font-size:13px;font-family:inherit;">
                        <option value="">— Choisir —</option>
                        @foreach($allCategoriesForNewProduct as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('newProductCategoryId') <div class="field-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-field">
                    <label>...ou nouvelle catégorie (laisse vide si tu as choisi au-dessus)</label>
                    <input type="text" wire:model="newProductCategoryName" placeholder="Ex : Antiseptiques">
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>Unité</label>
                        <input type="text" wire:model="newProductUnit" placeholder="{{ $unitPlaceholder }}">
                        @error('newProductUnit') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Seuil d'alerte</label>
                        <input type="number" step="1" min="0" wire:model="newProductThreshold">
                        @error('newProductThreshold') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="form-field">
                    <label>Quantité initiale en stock (optionnel — laisse à 0 si tu n'en as pas encore)</label>
                    <input type="number" step="1" min="0" wire:model="newProductInitialQty">
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label>N° de lot (optionnel)</label>
                        <input type="text" wire:model="newProductLotNumber" placeholder="Ex : L-4471">
                    </div>
                    <div class="form-field">
                        <label>Date de péremption (si quantité initiale)</label>
                        <input type="date" wire:model="newProductExpiryDate">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeNewProduct">Annuler</button>
                    <button type="submit" class="btn">Créer le produit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== Modale Modifier produit ===== --}}
    <div class="modal-backdrop {{ $showEditProductModal ? 'active' : '' }}" wire:click.self="closeEditProduct">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="serif">Modifier — {{ $editProductName }}</h3>
                <button class="modal-close" wire:click="closeEditProduct">✕</button>
            </div>
            <form wire:submit="saveEditProduct">
                <div class="form-field">
                    <label>Nom du produit</label>
                    <input type="text" wire:model="editProductName">
                    @error('editProductName') <div class="field-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Unité</label>
                        <input type="text" wire:model="editProductUnit">
                        @error('editProductUnit') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="form-field">
                        <label>Seuil d'alerte</label>
                        <input type="number" step="1" min="0" wire:model="editProductThreshold">
                        @error('editProductThreshold') <div class="field-error">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div style="font-size:11.5px;color:var(--ink-faint);margin-bottom:6px;">
                    Pour changer la quantité en stock, utilise plutôt "Mouvement" (entrée/sortie) — ça garde une trace, contrairement à une modification directe.
                </div>
                <div class="form-actions">
                    <button type="button" class="btn ghost" wire:click="closeEditProduct">Annuler</button>
                    <button type="submit" class="btn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
