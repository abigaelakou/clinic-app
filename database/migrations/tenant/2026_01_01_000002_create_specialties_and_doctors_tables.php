<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spécialités (gynécologie, pédiatrie, puis médecine personnalisée — cahier §1.3)
 * et fiche médecin. Conçu pour qu'ajouter une spécialité ou un médecin
 * ne nécessite aucune modification de code (cahier §3.3, évolutivité).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // "Gynécologie", "Pédiatrie"
            $table->string('color', 7)->default('#C0410C'); // pour l'agenda
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained();
            $table->string('title')->nullable();          // "Pr", "Dr"
            $table->text('bio')->nullable();
            $table->integer('consultation_duration_minutes')->default(30);
            $table->boolean('teleconsultation_enabled')->default(false);
            $table->timestamps();
        });

        // Créneaux d'ouverture récurrents + indisponibilités ponctuelles (cahier §5.2.1)
        Schema::create('doctor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['recurring', 'exception_closed', 'exception_open']);
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=dim ... 6=sam, si récurrent
            $table->date('specific_date')->nullable();              // si exception ponctuelle
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable(); // congé, formation...
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_availabilities');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('specialties');
    }
};
