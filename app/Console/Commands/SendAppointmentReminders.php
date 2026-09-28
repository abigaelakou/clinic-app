<?php

namespace App\Console\Commands;

use App\Mail\AppointmentStatusUpdate;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';
    protected $description = 'Envoie les rappels de rendez-vous par e-mail (48h, 24h, 4h avant) — à lancer régulièrement (toutes les heures).';

    /**
     * Fenêtre de tolérance : si la commande ne tourne pas exactement à
     * l'heure ronde, on considère quand même "48h avant" un créneau qui
     * tombe entre 47h et 49h à partir de maintenant, etc. Le suivi en
     * base (reminder_*_sent_at) empêche tout doublon même si la commande
     * tourne plusieurs fois dans la même fenêtre.
     */
    protected const WINDOWS = [
        '48h' => ['hours' => 48, 'column' => 'reminder_48h_sent_at'],
        '24h' => ['hours' => 24, 'column' => 'reminder_24h_sent_at'],
        '4h' => ['hours' => 4, 'column' => 'reminder_4h_sent_at'],
    ];

    public function handle(): int
    {
        $sentTotal = 0;

        foreach (self::WINDOWS as $label => $conf) {
            $targetStart = now()->addHours($conf['hours'])->subHour();
            $targetEnd = now()->addHours($conf['hours'])->addHour();

            $appointments = Appointment::whereBetween('scheduled_at', [$targetStart, $targetEnd])
                ->whereIn('status', ['confirmed'])
                ->whereNull($conf['column'])
                ->with('patient', 'doctor.user')
                ->get();

            foreach ($appointments as $appointment) {
                $email = $appointment->patient?->email;

                if ($email) {
                    try {
                        Mail::to($email)->send(new AppointmentStatusUpdate($appointment, 'reminder'));
                    } catch (\Throwable $e) {
                        $this->error("Échec envoi rappel {$label} pour RDV #{$appointment->id} : " . $e->getMessage());
                        continue;
                    }
                }

                // On marque comme "envoyé" même sans e-mail (pas d'adresse
                // renseignée) pour ne pas retenter en boucle à chaque run.
                $appointment->update([$conf['column'] => now()]);
                $sentTotal++;
            }

            $this->info("Rappels {$label} : {$appointments->count()} rendez-vous traités.");
        }

        $this->info("Total rappels envoyés : {$sentTotal}");

        return self::SUCCESS;
    }
}
