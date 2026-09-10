<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Stocks (cahier §5.1). Un seul modèle de données pour les trois
 * domaines (pharmacie / consommables-non consommables / cuisine), distingués
 * par la colonne "domain" — c'est ce qui permet d'ajouter facilement une
 * nouvelle catégorie de produits sans toucher au schéma (cahier §3.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_categories', function (Blueprint $table) {
            $table->id();
            $table->enum('domain', ['pharmacie', 'consommable', 'non_consommable', 'cuisine']);
            $table->string('name'); // "Antalgiques", "Hygiène des locaux", "Denrées alimentaires"...
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_category_id')->constrained();

            $table->string('name');
            $table->string('unit')->default('unité');        // boîte, sachet, kg, litre...
            $table->string('galenic_form')->nullable();       // forme galénique (pharmacie)
            $table->string('dosage')->nullable();
            $table->string('supplier')->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();

            // Stock courant dénormalisé pour affichage rapide (recalculé à
            // chaque mouvement — la vérité reste stock_movements + batches).
            $table->decimal('quantity_on_hand', 12, 2)->default(0);
            $table->decimal('alert_threshold', 12, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['stock_category_id', 'quantity_on_hand']);
        });

        // Un produit peut avoir plusieurs lots (numéro de lot + péremption) — cahier §5.1.1
        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->timestamps();

            $table->index('expiry_date');
        });

        // Historique complet des entrées/sorties, à des fins d'audit (cahier §5.1.6)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('product_batch_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['entry', 'exit']);
            $table->decimal('quantity', 12, 2);

            // Traçabilité "qui, quoi, quand, combien" (objectif §2.2)
            $table->foreignId('user_id')->constrained();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete(); // dispensation nominative
            $table->string('service')->nullable();     // bloc, consultation, sanitaires... (cahier §5.1.2)
            $table->string('reason')->nullable();       // réception fournisseur, dispensation, péremption...

            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stock_categories');
    }
};
