<?php

namespace App\Mail;

use App\Models\Doctor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DoctorScheduleReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Doctor $doctor,
        public \Carbon\Carbon $day,
        public Collection $appointments,
    ) {}

    public function build()
    {
        return $this->subject('Ton planning du ' . $this->day->translatedFormat('d/m') . ' — CLINIQUE FAME')
            ->view('emails.doctor-schedule');
    }
}
