<?php

namespace App\Console\Commands;

use App\Mail\DoctorScheduleReminder;
use App\Models\Doctor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDoctorScheduleReminders extends Command
{
    protected $signature = 'appointments:send-doctor-schedule';
    protected $description = "Envoie à chaque médecin le récapitulatif de son planning — à lancer à 8h et 20h.";

    public function handle(): int
    {
        // Le matin (avant midi) : planning du jour. Le soir : planning du lendemain.
        $targetDay = now()->hour < 12 ? today() : today()->addDay();

        $doctors = Doctor::with(['user', 'appointments' => function ($q) use ($targetDay) {
            $q->whereDate('scheduled_at', $targetDay)
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('scheduled_at')
                ->with('patient');
        }])->get();

        $sent = 0;

        foreach ($doctors as $doctor) {
            $email = $doctor->user?->email;

            if (! $email || $doctor->appointments->isEmpty()) {
                continue;
            }

            try {
                Mail::to($email)->send(new DoctorScheduleReminder($doctor, $targetDay, $doctor->appointments));
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Échec envoi planning pour {$doctor->user->name} : " . $e->getMessage());
            }
        }

        $this->info("Rappels de planning envoyés à {$sent} médecin(s) pour le " . $targetDay->translatedFormat('d/m/Y') . ".");

        return self::SUCCESS;
    }
}
