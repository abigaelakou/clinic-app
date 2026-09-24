<?php

namespace App\Livewire;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Product;
use App\Models\StockCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Stocks extends Component
{
    use WithPagination;

    public string $domain = '';
    public ?int $categoryId = null;

    // ---- Modale mouvement ----
    public bool $showMovementModal = false;
    public ?int $movementProductId = null;
    public string $movementProductName = '';
    public string $movementType = 'entry';
    public $movementQuantity = 1;
    public string $movementReason = '';
    public string $movementLotNumber = '';
    public string $movementExpiryDate = '';
    public ?int $movementPatientId = null;
    public string $selectedPatientName = '';
    public string $patientSearch = '';
    public string $movementService = '';
    public ?int $movementPrescribingDoctorId = null;
    public string $selectedPrescribingDoctorName = '';
    public string $prescribingDoctorSearch = '';

    // ---- Modale historique ----
    public bool $showHistoryModal = false;
    public ?int $historyProductId = null;
    public string $historyProductName = '';

    // ---- Modale nouveau produit ----
    public bool $showNewProductModal = false;
    public string $newProductName = '';
    public ?int $newProductCategoryId = null;
    public string $newProductCategoryName = '';
    public string $newProductUnit = '';
    public $newProductThreshold = 0;
    public $newProductInitialQty = 0;
    public string $newProductLotNumber = '';
    public string $newProductExpiryDate = '';

    // ---- Modale réapprovisionnement ----
    public bool $showReorderModal = false;

    /**
     * Domaines où la dispensation nominative (lier une sortie à une
     * patiente) a du sens. Pas la cuisine ou le non consommable générique.
     */
    protected const PATIENT_LINK_DOMAINS = ['pharmacie', 'consommable'];

    /** Domaines où affecter une sortie à un service (bloc, consultation...) a du sens. */
    protected const SERVICE_DOMAINS = ['consommable', 'non_consommable'];

    public const SERVICES = [
        'bloc' => 'Bloc',
        'consultation' => 'Consultation',
        'administration' => 'Administration',
        'sanitaires' => 'Sanitaires',
    ];

    public function mount()
    {
        $readable = Auth::user()->readableStockDomains();

        if (empty($readable)) {
            abort(403, "Ton rôle n'a accès à aucun domaine de stock.");
        }

        $this->domain = $readable[0];
    }

    public function selectDomain(string $domain)
    {
        if (! Auth::user()->canReadStockDomain($domain)) {
            abort(403);
        }

        $this->domain = $domain;
        $this->categoryId = null;
        $this->resetPage();
    }

    public function selectCategory(?int $categoryId)
    {
        $this->categoryId = $categoryId;
        $this->resetPage();
    }

    protected function domainLabel(string $domain): string
    {
        return match ($domain) {
            'pharmacie' => 'Pharmacie',
            'consommable' => 'Consommables',
            'non_consommable' => 'Non consommables',
            'cuisine' => 'Cuisine',
            default => ucfirst($domain),
        };
    }

    /** Exemple de nom de produit adapté au domaine affiché, pour guider la saisie. */
    protected function productPlaceholder(string $domain): string
    {
        return match ($domain) {
            'pharmacie' => 'Ex : Paracétamol 500mg',
            'consommable' => 'Ex : Gants latex, taille M',
            'non_consommable' => 'Ex : Tensiomètre',
            'cuisine' => 'Ex : Riz local (sac 25kg)',
            default => 'Nom du produit',
        };
    }

    protected function unitPlaceholder(string $domain): string
    {
        return match ($domain) {
            'pharmacie' => 'comprimé, dose, flacon...',
            'consommable' => 'boîte, paquet, rouleau...',
            'non_consommable' => 'unité, appareil...',
            'cuisine' => 'kg, sac, litre...',
            default => 'unité',
        };
    }

    // ---- Modale édition produit ----
    public bool $showEditProductModal = false;

    // ---- Modale de confirmation générique ----
    public bool $showConfirmModal = false;
    public string $confirmMessage = '';
    public ?int $confirmTargetId = null;

    public function askConfirmDelete(int $productId, string $productName)
    {
        $this->confirmTargetId = $productId;
        $this->confirmMessage = 'Retirer "' . $productName . '" du stock ?';
        $this->showConfirmModal = true;
    }

    public function closeConfirm()
    {
        $this->showConfirmModal = false;
    }

    public function runConfirmedAction()
    {
        $this->reallyDeleteProduct($this->confirmTargetId);
        $this->showConfirmModal = false;
    }

    public ?int $editProductId = null;
    public string $editProductName = '';
    public string $editProductUnit = '';
    public $editProductThreshold = 0;

    public function openEditProduct(int $productId)
    {
        $product = Product::with('category')->findOrFail($productId);

        if (! Auth::user()->canWriteStockDomain($product->category->domain)) {
            abort(403);
        }

        $this->editProductId = $product->id;
        $this->editProductName = $product->name;
        $this->editProductUnit = $product->unit;
        $this->editProductThreshold = $product->alert_threshold;
        $this->resetErrorBag();
        $this->showEditProductModal = true;
    }

    public function closeEditProduct()
    {
        $this->showEditProductModal = false;
    }

    public function saveEditProduct()
    {
        $this->validate([
            'editProductName' => 'required|min:2',
            'editProductUnit' => 'required',
            'editProductThreshold' => 'required|numeric|min:0',
        ], [], ['editProductName' => 'nom', 'editProductUnit' => 'unité', 'editProductThreshold' => "seuil d'alerte"]);

        $product = Product::with('category')->findOrFail($this->editProductId);

        if (! Auth::user()->canWriteStockDomain($product->category->domain)) {
            abort(403);
        }

        $product->update([
            'name' => $this->editProductName,
            'unit' => $this->editProductUnit,
            'alert_threshold' => $this->editProductThreshold,
        ]);

        $this->showEditProductModal = false;
        $this->dispatch('toast', message: 'Produit mis à jour.');
    }

    /**
     * Désactive le produit plutôt que de le supprimer si des mouvements
     * existent déjà (on ne perd jamais l'historique) ; suppression réelle
     * seulement si aucun mouvement n'a jamais été enregistré.
     */
    protected function reallyDeleteProduct(?int $productId)
    {
        $product = Product::with('category')->findOrFail($productId);

        if (! Auth::user()->canWriteStockDomain($product->category->domain)) {
            abort(403);
        }

        if ($product->movements()->exists()) {
            $product->update(['is_active' => false]);
            $this->dispatch('toast', message: $product->name . ' désactivé (historique conservé).');
        } else {
            $product->delete();
            $this->dispatch('toast', message: $product->name . ' supprimé.');
        }
    }

    public function openMovement(int $productId)
    {
        $product = Product::with('category')->findOrFail($productId);

        if (! Auth::user()->canWriteStockDomain($product->category->domain)) {
            abort(403);
        }

        $this->movementProductId = $product->id;
        $this->movementProductName = $product->name;
        $this->movementType = 'entry';
        $this->movementQuantity = 1;
        $this->movementReason = '';
        $this->movementLotNumber = '';
        $this->movementExpiryDate = '';
        $this->movementPatientId = null;
        $this->selectedPatientName = '';
        $this->patientSearch = '';
        $this->movementService = '';
        $this->movementPrescribingDoctorId = null;
        $this->selectedPrescribingDoctorName = '';
        $this->prescribingDoctorSearch = '';
        $this->resetErrorBag();
        $this->showMovementModal = true;
    }

    public function closeMovement()
    {
        $this->showMovementModal = false;
    }

    public function selectMovementPatient(int $patientId)
    {
        $patient = Patient::findOrFail($patientId);
        $this->movementPatientId = $patient->id;
        $this->selectedPatientName = $patient->first_name . ' ' . $patient->last_name;
        $this->patientSearch = '';
    }

    public function clearMovementPatient()
    {
        $this->movementPatientId = null;
        $this->selectedPatientName = '';
    }

    public function selectPrescribingDoctor(int $doctorId)
    {
        $doctor = Doctor::with('user')->findOrFail($doctorId);
        $this->movementPrescribingDoctorId = $doctor->id;
        $this->selectedPrescribingDoctorName = $doctor->user->name ?? '';
        $this->prescribingDoctorSearch = '';
    }

    public function clearPrescribingDoctor()
    {
        $this->movementPrescribingDoctorId = null;
        $this->selectedPrescribingDoctorName = '';
    }

    public function saveMovement()
    {
        $this->validate([
            'movementType' => 'required|in:entry,exit',
            'movementQuantity' => 'required|numeric|min:0.01',
            'movementLotNumber' => 'nullable|string|max:100',
            'movementExpiryDate' => 'nullable|date',
        ], [], ['movementQuantity' => 'quantité']);

        $product = Product::with('category')->findOrFail($this->movementProductId);

        if (! Auth::user()->canWriteStockDomain($product->category->domain)) {
            abort(403);
        }

        if ($this->movementType === 'exit' && (float) $this->movementQuantity > $product->quantity_on_hand) {
            $this->addError('movementQuantity', 'Quantité supérieure au stock disponible (' . $product->formattedQuantity() . ').');
            return;
        }

        $canLinkPatient = in_array($product->category->domain, self::PATIENT_LINK_DOMAINS, true);
        $canLinkService = in_array($product->category->domain, self::SERVICE_DOMAINS, true);
        $canLinkPrescriber = $product->category->domain === 'pharmacie';

        $product->recordMovement(
            $this->movementType,
            (float) $this->movementQuantity,
            Auth::user(),
            [
                'reason' => $this->movementReason ?: null,
                'lot_number' => $this->movementLotNumber ?: null,
                'expiry_date' => $this->movementExpiryDate ?: null,
                'patient_id' => ($this->movementType === 'exit' && $canLinkPatient) ? $this->movementPatientId : null,
                'service' => ($this->movementType === 'exit' && $canLinkService) ? ($this->movementService ?: null) : null,
                'prescribing_doctor_id' => ($this->movementType === 'exit' && $canLinkPrescriber) ? $this->movementPrescribingDoctorId : null,
            ]
        );

        $this->showMovementModal = false;
        $this->dispatch('toast', message: 'Mouvement enregistré pour ' . $product->name . '.');
    }

    // ---------- Historique ----------

    public function openHistory(int $productId)
    {
        $product = Product::with('category')->findOrFail($productId);

        if (! Auth::user()->canReadStockDomain($product->category->domain)) {
            abort(403);
        }

        $this->historyProductId = $productId;
        $this->historyProductName = $product->name;
        $this->showHistoryModal = true;
    }

    public function closeHistory()
    {
        $this->showHistoryModal = false;
    }

    // ---------- Nouveau produit ----------

    public function openNewProduct()
    {
        if (! Auth::user()->canWriteStockDomain($this->domain)) {
            abort(403);
        }

        $this->newProductName = '';
        $this->newProductCategoryId = null;
        $this->newProductCategoryName = '';
        $this->newProductUnit = '';
        $this->newProductThreshold = 0;
        $this->newProductInitialQty = 0;
        $this->newProductLotNumber = '';
        $this->newProductExpiryDate = '';
        $this->resetErrorBag();
        $this->showNewProductModal = true;
    }

    public function closeNewProduct()
    {
        $this->showNewProductModal = false;
    }

    public function saveNewProduct()
    {
        if (! Auth::user()->canWriteStockDomain($this->domain)) {
            abort(403);
        }

        $this->validate([
            'newProductName' => 'required|min:2',
            'newProductUnit' => 'required',
            'newProductThreshold' => 'required|numeric|min:0',
            'newProductInitialQty' => 'nullable|numeric|min:0',
            'newProductExpiryDate' => 'nullable|date',
        ], [], [
            'newProductName' => 'nom du produit',
            'newProductUnit' => 'unité',
            'newProductThreshold' => "seuil d'alerte",
        ]);

        if (! $this->newProductCategoryId && trim($this->newProductCategoryName) === '') {
            $this->addError('newProductCategoryId', 'Choisis une catégorie existante ou saisis-en une nouvelle.');
            return;
        }

        $categoryId = $this->newProductCategoryId;

        if (! $categoryId) {
            $category = StockCategory::firstOrCreate([
                'domain' => $this->domain,
                'name' => trim($this->newProductCategoryName),
            ]);
            $categoryId = $category->id;
        }

        $product = Product::create([
            'stock_category_id' => $categoryId,
            'name' => $this->newProductName,
            'unit' => $this->newProductUnit,
            'alert_threshold' => $this->newProductThreshold,
            'quantity_on_hand' => 0,
            'is_active' => true,
        ]);

        // La quantité initiale est optionnelle : on peut référencer un produit
        // avant d'en avoir physiquement en stock (ex: en attente de livraison).
        if ((float) $this->newProductInitialQty > 0) {
            $product->recordMovement('entry', (float) $this->newProductInitialQty, Auth::user(), [
                'reason' => 'Stock initial à la création du produit',
                'lot_number' => $this->newProductLotNumber ?: null,
                'expiry_date' => $this->newProductExpiryDate ?: null,
            ]);
        }

        $this->categoryId = $categoryId;
        $this->showNewProductModal = false;
        $this->dispatch('toast', message: $product->name . ' ajouté au stock.');
    }

    // ---------- Réapprovisionnement ----------

    public function openReorder()
    {
        $this->showReorderModal = true;
    }

    public function closeReorder()
    {
        $this->showReorderModal = false;
    }

    public function render()
    {
        $user = Auth::user();
        $readableDomains = $user->readableStockDomains();

        $categories = StockCategory::where('domain', $this->domain)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $productsQuery = Product::whereHas('category', fn ($q) => $q->where('domain', $this->domain))
            ->where('is_active', true)
            ->with(['category', 'batches' => fn ($q) => $q->where('quantity', '>', 0)->whereNotNull('expiry_date')->orderBy('expiry_date')]);

        if ($this->categoryId) {
            $productsQuery->where('stock_category_id', $this->categoryId);
        }

        $products = $productsQuery->orderBy('name')->simplePaginate(12);

        $statsQuery = fn () => Product::whereHas('category', fn ($q) => $q->where('domain', $this->domain))
            ->where('is_active', true)
            ->when($this->categoryId, fn ($q) => $q->where('stock_category_id', $this->categoryId));

        $movementHistory = collect();
        if ($this->historyProductId) {
            $movementHistory = \App\Models\StockMovement::where('product_id', $this->historyProductId)
                ->with(['user', 'patient', 'prescribingDoctor.user'])
                ->latest()
                ->limit(20)
                ->get();
        }

        $filteredPatients = $this->patientSearch !== ''
            ? Patient::where('first_name', 'like', "%{$this->patientSearch}%")->orWhere('last_name', 'like', "%{$this->patientSearch}%")->limit(8)->get()
            : Patient::orderBy('first_name')->limit(8)->get();

        $filteredPrescribingDoctors = Doctor::with('user')
            ->when($this->prescribingDoctorSearch !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->prescribingDoctorSearch}%")))
            ->limit(8)
            ->get();

        // Produits à réapprovisionner sur le domaine affiché.
        $reorderList = Product::whereHas('category', fn ($q) => $q->where('domain', $this->domain))
            ->where('is_active', true)
            ->belowThreshold()
            ->with('category')
            ->orderBy('quantity_on_hand')
            ->get();

        return view('livewire.stocks', [
            'domainTabs' => collect($readableDomains)->map(fn ($d) => ['key' => $d, 'label' => $this->domainLabel($d)]),
            'currentLabel' => $this->domainLabel($this->domain),
            'categories' => $categories,
            'allCategoriesForNewProduct' => $categories,
            'products' => $products,
            'total' => $statsQuery()->count(),
            'ruptureCount' => $statsQuery()->outOfStock()->count(),
            'lowStockCount' => $statsQuery()->belowThreshold()->where('quantity_on_hand', '>', 0)->count(),
            'canWrite' => $user->canWriteStockDomain($this->domain),
            'movementHistory' => $movementHistory,
            'filteredPatients' => $filteredPatients,
            'filteredPrescribingDoctors' => $filteredPrescribingDoctors,
            'productPlaceholder' => $this->productPlaceholder($this->domain),
            'unitPlaceholder' => $this->unitPlaceholder($this->domain),
            'canLinkPatient' => in_array($this->domain, self::PATIENT_LINK_DOMAINS, true),
            'canLinkService' => in_array($this->domain, self::SERVICE_DOMAINS, true),
            'canLinkPrescriber' => $this->domain === 'pharmacie',
            'services' => self::SERVICES,
            'reorderList' => $reorderList,
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
