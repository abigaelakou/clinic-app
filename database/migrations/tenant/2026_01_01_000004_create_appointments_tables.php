<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module Rendez-vous (cahier §5.2). Une demande patiente reste "pending"
 * tant qu'elle n'est pas validée par la réception ou le médecin ; le
 * signalement d'indisponibilité par un médecin déclenche des notifications
 * automatiques (voir AppointmentObserver côté application).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('doctor_id')->constrained();
            $table->foreignId('specialty_id')->constrained();

            $table->dateTime('scheduled_at');
            $table->integer('duration_minutes')->default(30);

            $table->enum('status', [
                'pending',      // demandée par la patiente, non confirmée
                'confirmed',
                'rescheduled',
                'cancelled',
                'completed',
                'no_show',
            ])->default('pending');

            $table->enum('type', ['in_person', 'teleconsultation'])->default('in_person');
            $table->string('reason')->nullable(); // motif indiqué à la prise de RDV

            // Qui a initié / confirmé (patiente vs réception vs médecin) — cahier §5.2.1
            $table->enum('requested_by', ['patient', 'reception', 'doctor'])->default('reception');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('presence_confirmation_requested')->default(false);
            $table->boolean('presence_confirmed')->nullable();

            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'scheduled_at']);
        });

        // Historique de toutes les modifications (annulation, report, motif) — cahier §5.2.3
        Schema::create('appointment_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // Rappels programmés : 48h / 24h / 4h avant, configurables (cahier §5.2.2)
        Schema::create('appointment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->enum('channel', ['sms', 'email', 'in_app']);
            $table->dateTime('send_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reminders');
        Schema::dropIfExists('appointment_status_logs');
        Schema::dropIfExists('appointments');
    }
};
