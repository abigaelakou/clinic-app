<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Types de consultation propres à chaque clinique (ex: G = Grossesse,
 * GO = Gynécologie, C = Cancer...) — volontairement pas codés en dur,
 * chaque clinique gère sa propre liste, utile pour la revente à
 * d'autres établissements qui n'auront pas les mêmes catégories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10);     // "G", "GO", "C"...
            $table->string('label');        // "Grossesse", "Gynécologie", "Cancer"...
            $table->string('color', 7)->default('#C0410C');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('code');
        });

        Schema::table('consultations', function (Blueprint $table) {
            $table->foreignId('consultation_type_id')->nullable()->after('doctor_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('consultation_type_id');
        });

        Schema::dropIfExists('consultation_types');
    }
};
