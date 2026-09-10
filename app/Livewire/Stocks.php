<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\StockCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Stocks extends Component
{
    public string $domain = '';
    public ?int $categoryId = null;

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
    }

    public function selectCategory(?int $categoryId)
    {
        $this->categoryId = $categoryId;
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

    public function render()
    {
        $user = Auth::user();
        $readableDomains = $user->readableStockDomains();

        $categories = StockCategory::where('domain', $this->domain)
            ->withCount('products')
            ->orderBy('name')
            ->get();

        $productsQuery = Product::whereHas('category', fn ($q) => $q->where('domain', $this->domain))
            ->with('category');

        if ($this->categoryId) {
            $productsQuery->where('stock_category_id', $this->categoryId);
        }

        $products = $productsQuery->orderBy('name')->get();

        $readableFilter = fn ($q) => $q->whereIn('domain', $readableDomains);

        return view('livewire.stocks', [
            'domainTabs' => collect($readableDomains)->map(fn ($d) => ['key' => $d, 'label' => $this->domainLabel($d)]),
            'currentLabel' => $this->domainLabel($this->domain),
            'categories' => $categories,
            'products' => $products,
            'total' => Product::whereHas('category', $readableFilter)->count(),
            'ruptureCount' => Product::outOfStock()->whereHas('category', $readableFilter)->count(),
            'lowStockCount' => Product::belowThreshold()->where('quantity_on_hand', '>', 0)->whereHas('category', $readableFilter)->count(),
            'canWrite' => $user->canWriteStockDomain($this->domain),
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
