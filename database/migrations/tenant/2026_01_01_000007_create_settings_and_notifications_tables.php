<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramétrage clinique (module Administration, cahier §5.4) + notifications
 * in-app. La table "notifications" suit le format standard des notifications
 * Laravel (Notifiable trait), directement compatible avec le panneau de
 * notifications du tableau de bord.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();  // "reminder_hours_before", "alert_email_enabled"...
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Format standard Laravel (Illuminate\Notifications\Notifiable)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');   // notifiable_type + notifiable_id (ex: User)
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('settings');
    }
};
