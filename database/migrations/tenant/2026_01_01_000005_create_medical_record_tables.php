<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dossier médical (cahier §5.3.1) : historique des consultations, documents,
 * et constantes prises par l'aide-soignant. Le champ share_with_patient
 * sur medical_documents est ce qui alimente "Documents partagés" côté
 * espace patiente (accès limité, cahier §5.3.2) — un document n'apparaît
 * chez la patiente QUE si le médecin l'a explicitement partagé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('consulted_at');
            $table->string('motif');
            $table->text('diagnostic')->nullable();
            $table->text('prescriptions')->nullable();
            $table->text('exams_requested')->nullable();
            $table->text('exam_results')->nullable();

            $table->boolean('is_teleconsultation')->default(false);

            $table->timestamps();
        });

        Schema::create('medical_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users');

            $table->string('title');
            $table->enum('type', ['ordonnance', 'resultat_examen', 'compte_rendu', 'autre']);
            $table->string('file_path');

            // Un document n'est visible par la patiente que si ceci est vrai
            // (cf. "Espace patiente (accès limité)" — cahier §5.3.2)
            $table->boolean('shared_with_patient')->default(false);

            $table->timestamps();
        });

        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('recorded_by')->constrained('users'); // aide-soignant / sage-femme
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 1)->nullable();
            $table->string('blood_pressure', 15)->nullable(); // "120/80"
            $table->decimal('temperature_c', 4, 1)->nullable();
            $table->integer('heart_rate')->nullable();

            $table->timestamp('recorded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
        Schema::dropIfExists('medical_documents');
        Schema::dropIfExists('consultations');
    }
};
