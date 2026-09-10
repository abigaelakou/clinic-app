<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier administratif de la patiente (cahier §5.3.3) : création prioritaire
 * par la réception, avec auto-inscription en ligne en option et rattachement
 * automatique pour éviter les doublons (nom + date de naissance + téléphone).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Compte de connexion optionnel (auto-inscription). Peut être NULL si le
            // dossier a été créé par la réception sans que la patiente ait encore
            // de compte -> évite les comptes fantômes, cf. cahier §5.3.3.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('sex', ['F', 'M'])->default('F');
            $table->string('phone')->unique();   // clé de rattachement automatique
            $table->string('email')->nullable();
            $table->string('address')->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();

            $table->text('declared_history')->nullable(); // antécédents déclarés

            // Traçabilité de création (réception vs auto-inscription) — cahier §5.3.3
            $table->enum('created_via', ['reception', 'self_registration'])->default('reception');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('identity_verified')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['last_name', 'first_name']);
        });

        // Journalisation de toute consultation du dossier — obligatoire pour la
        // confidentialité (cahier §5.3.1 : "qui a consulté, quand").
        Schema::create('medical_record_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->string('action'); // "view", "edit", "export"...
            $table->timestamp('accessed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_access_logs');
        Schema::dropIfExists('patients');
    }
};
