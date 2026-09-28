<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ces commandes tournent pour CHAQUE clinique (tenants:run), pas juste
// la base centrale — indispensable en multi-tenant.
Schedule::command('tenants:run appointments:send-reminders')->hourly();
Schedule::command('tenants:run appointments:send-doctor-schedule')->dailyAt('08:00');
Schedule::command('tenants:run appointments:send-doctor-schedule')->dailyAt('20:00');
