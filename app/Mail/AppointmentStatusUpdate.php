<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentStatusUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment,
        public string $kind, // 'confirmed' | 'cancelled' | 'rescheduled'
    ) {}

    public function build()
    {
        $subjects = [
            'confirmed' => 'Ton rendez-vous est confirmé',
            'cancelled' => 'Ton rendez-vous a été annulé',
            'rescheduled' => 'Ton rendez-vous a été reporté',
        ];

        return $this->subject($subjects[$this->kind] . ' — CLINIQUE FAME')
            ->view('emails.appointment-status');
    }
}
