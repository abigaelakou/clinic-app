<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Rdv extends Component
{
    use WithPagination;

    public string $selectedDate = '';
    public string $viewMode = 'day'; // 'day' ou 'week'

    // ---- Annulation ----
    public bool $showCancelModal = false;
    public ?int $cancelAppointmentId = null;
    public string $cancelReason = '';

    // ---- Report ----
    public bool $showRescheduleModal = false;
    public ?int $rescheduleAppointmentId = null;
    public string $rescheduleDate = '';
    public string $rescheduleTime = '';

    // ---- Indisponibilité (sur une plage de dates, avec heures optionnelles) ----
    public bool $showUnavailableModal = false;
    public string $unavailableStartDate = '';
    public string $unavailableEndDate = '';
    public string $unavailableStart = '08:00';
    public string $unavailableEnd = '18:00';
    public bool $unavailableAllDay = true;
    public string $unavailableReason = '';
    public array $conflictingAppointments = [];

    public function mount()
    {
        $this->selectedDate = today()->toDateString();
    }

    public function selectDay(string $date)
    {
        $this->selectedDate = $date;
        $this->resetPage('dayPage');
    }

    public function setViewMode(string $mode)
    {
        $this->viewMode = $mode;
    }

    public function confirm(int $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if (! Auth::user()->can('confirm', $appointment)) {
            abort(403);
        }

        $appointment->confirm(Auth::user());

        $this->dispatch('toast', message: 'Rendez-vous confirmé.');
    }

    public function markCompleted(int $appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        if (! Auth::user()->can('update', $appointment)) {
            abort(403);
        }

        $appointment->statusLogs()->create([
            'from_status' => $appointment->status,
            'to_status' => 'completed',
            'changed_by' => Auth::id(),
        ]);

        $appointment->update(['status' => 'completed']);

        $this->dispatch('toast', message: 'Rendez-vous marqué comme terminé.');
    }

    /** Suppression définitive — réservée à l'admin, contrairement à "Annuler" qui garde une trace. */
    public function deleteAppointment(int $id)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403);
        }

        Appointment::findOrFail($id)->delete();

        $this->dispatch('toast', message: 'Rendez-vous supprimé définitivement.');
    }

    public function openCancel(int $id)
    {
        $appointment = Appointment::findOrFail($id);

        if (! Auth::user()->can('update', $appointment)) {
            abort(403);
        }

        $this->cancelAppointmentId = $id;
        $this->cancelReason = '';
        $this->resetErrorBag();
        $this->showCancelModal = true;
    }

    public function closeCancel()
    {
        $this->showCancelModal = false;
    }

    public function saveCancel()
    {
        $this->validate(['cancelReason' => 'required|min:3'], [], ['cancelReason' => 'motif']);

        $appointment = Appointment::findOrFail($this->cancelAppointmentId);

        if (! Auth::user()->can('update', $appointment)) {
            abort(403);
        }

        $appointment->statusLogs()->create([
            'from_status' => $appointment->status,
            'to_status' => 'cancelled',
            'changed_by' => Auth::id(),
            'reason' => $this->cancelReason,
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $this->cancelReason,
        ]);

        $this->showCancelModal = false;
        $this->dispatch('toast', message: 'Rendez-vous annulé.');
    }

    public function openReschedule(int $id)
    {
        $appointment = Appointment::findOrFail($id);

        if (! Auth::user()->can('update', $appointment)) {
            abort(403);
        }

        $this->rescheduleAppointmentId = $id;
        $this->rescheduleDate = $appointment->scheduled_at->toDateString();
        $this->rescheduleTime = $appointment->scheduled_at->format('H:i');
        $this->resetErrorBag();
        $this->showRescheduleModal = true;
    }

    public function closeReschedule()
    {
        $this->showRescheduleModal = false;
    }

    public function saveReschedule()
    {
        $this->validate([
            'rescheduleDate' => 'required|date',
            'rescheduleTime' => 'required',
        ], [], ['rescheduleDate' => 'date', 'rescheduleTime' => 'heure']);

        $appointment = Appointment::findOrFail($this->rescheduleAppointmentId);

        if (! Auth::user()->can('update', $appointment)) {
            abort(403);
        }

        $old = $appointment->scheduled_at->format('d/m à H:i');

        $appointment->statusLogs()->create([
            'from_status' => $appointment->status,
            'to_status' => 'rescheduled',
            'changed_by' => Auth::id(),
            'reason' => 'Reporté depuis le ' . $old,
        ]);

        $appointment->update([
            'scheduled_at' => $this->rescheduleDate . ' ' . $this->rescheduleTime,
            'status' => 'confirmed',
        ]);

        $this->showRescheduleModal = false;
        $this->dispatch('toast', message: 'Rendez-vous reporté.');
    }

    // ---------- Indisponibilité ----------

    public function openUnavailable()
    {
        $user = Auth::user();

        if (! in_array($user->role, ['medecin', 'admin'], true) || ! $user->doctor) {
            abort(403);
        }

        $this->unavailableStartDate = today()->toDateString();
        $this->unavailableEndDate = today()->toDateString();
        $this->unavailableStart = '08:00';
        $this->unavailableEnd = '18:00';
        $this->unavailableAllDay = true;
        $this->unavailableReason = '';
        $this->conflictingAppointments = [];
        $this->resetErrorBag();
        $this->showUnavailableModal = true;
    }

    public function closeUnavailable()
    {
        $this->showUnavailableModal = false;
    }

    public function saveUnavailable()
    {
        $this->validate([
            'unavailableStartDate' => 'required|date',
            'unavailableEndDate' => 'required|date|after_or_equal:unavailableStartDate',
            'unavailableReason' => 'required|min:3',
        ], [], [
            'unavailableStartDate' => 'date de début', 'unavailableEndDate' => 'date de fin',
            'unavailableReason' => 'motif',
        ]);

        $doctor = Auth::user()->doctor;

        $startTime = $this->unavailableAllDay ? '00:00' : $this->unavailableStart;
        $endTime = $this->unavailableAllDay ? '23:59' : $this->unavailableEnd;

        $rangeStart = $this->unavailableStartDate . ' ' . $startTime;
        $rangeEnd = $this->unavailableEndDate . ' ' . $endTime;

        // On cherche les RDV déjà pris sur toute la période, pour prévenir
        // tout de suite la personne qui déclare l'indisponibilité (pas
        // encore de notification push vers la réception — voir README).
        $conflicts = Appointment::where('doctor_id', $doctor->id)
            ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd])
            ->whereNotIn('status', ['cancelled'])
            ->with('patient')
            ->get();

        // Une ligne par jour de la période (le schéma ne stocke qu'une date
        // ponctuelle par ligne, donc un congé de 3 jours = 3 lignes).
        $start = \Carbon\Carbon::parse($this->unavailableStartDate);
        $end = \Carbon\Carbon::parse($this->unavailableEndDate);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            DoctorAvailability::create([
                'doctor_id' => $doctor->id,
                'type' => 'exception_closed',
                'specific_date' => $day->toDateString(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'reason' => $this->unavailableReason,
            ]);
        }

        if ($conflicts->isNotEmpty()) {
            $this->conflictingAppointments = $conflicts->map(fn ($a) => [
                'patient' => $a->patient->first_name ?? '—',
                'date' => $a->scheduled_at->format('d/m'),
                'time' => $a->scheduled_at->format('H:i'),
            ])->toArray();

            $this->dispatch('toast', message: $conflicts->count() . ' rendez-vous existant(s) sur cette période — à recontacter toi-même pour l’instant.');
        } else {
            $this->showUnavailableModal = false;
            $this->dispatch('toast', message: 'Indisponibilité enregistrée.');
        }
    }

    /** Se contente de provoquer un re-rendu pour rafraîchir agenda + demandes. */
    #[On('appointment-created')]
    public function refreshAfterCreate()
    {
        //
    }

    public function render()
    {
        $user = Auth::user();

        if (! $user->canAccessAppointments()) {
            abort(403, "Ton rôle n'a pas accès au module Rendez-vous.");
        }

        $selected = \Carbon\Carbon::parse($this->selectedDate);

        $dayQuery = Appointment::whereDate('scheduled_at', $selected)->orderBy('scheduled_at')->with(['patient', 'doctor.user']);
        $pendingQuery = Appointment::pending()->with(['patient', 'doctor.user'])->orderBy('scheduled_at');

        if ($user->role === 'medecin' && $user->doctor && ! $user->seesAllAppointments()) {
            $dayQuery->where('doctor_id', $user->doctor->id);
            $pendingQuery->where('doctor_id', $user->doctor->id);
        } elseif ($user->role === 'medecin' && ! $user->doctor) {
            $dayQuery->whereRaw('1 = 0');
            $pendingQuery->whereRaw('1 = 0');
        }

        $dayAppointmentsAll = (clone $dayQuery)->get(); // pour la répartition "par médecin", non paginée
        $dayAppointments = (clone $dayQuery)->simplePaginate(10, ['*'], 'dayPage'); // pour l'affichage de l'agenda
        $pending = $pendingQuery->simplePaginate(5, ['*'], 'pendingPage');
        $byDoctor = $dayAppointmentsAll->groupBy(fn ($a) => $a->doctor->user->name ?? 'Médecin');

        $weekStart = $selected->copy()->startOfWeek();
        $weekDays = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));

        // Indisponibilités : un médecin voit les siennes (bandeau + repère
        // sur ses propres jours) ; admin/réception voient celles de TOUS
        // les médecins (utile pour la prise de RDV et la coordination).
        $myUnavailabilityToday = null;
        $unavailableDatesInWeek = [];
        $othersUnavailableToday = collect();

        if ($user->role === 'medecin' && $user->doctor) {
            $doctorId = $user->doctor->id;

            $myUnavailabilityToday = DoctorAvailability::where('doctor_id', $doctorId)
                ->where('specific_date', $selected->toDateString())
                ->first();

            $unavailableDatesInWeek = DoctorAvailability::where('doctor_id', $doctorId)
                ->whereBetween('specific_date', [$weekStart->toDateString(), $weekStart->copy()->endOfWeek()->toDateString()])
                ->pluck('specific_date')
                ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
                ->unique()->values()->toArray();
        } elseif (in_array($user->role, ['admin', 'reception'], true)) {
            $othersUnavailableToday = DoctorAvailability::where('specific_date', $selected->toDateString())
                ->with('doctor.user')
                ->get();

            $unavailableDatesInWeek = DoctorAvailability::whereBetween('specific_date', [$weekStart->toDateString(), $weekStart->copy()->endOfWeek()->toDateString()])
                ->pluck('specific_date')
                ->map(fn ($d) => \Carbon\Carbon::parse($d)->toDateString())
                ->unique()->values()->toArray();
        }

        // Pour la vue semaine : tous les RDV de la semaine, groupés par jour.
        $weekAppointments = [];
        if ($this->viewMode === 'week') {
            $weekEnd = $weekStart->copy()->endOfWeek();
            $weekQuery = Appointment::whereBetween('scheduled_at', [$weekStart->copy()->startOfDay(), $weekEnd->copy()->endOfDay()])
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('scheduled_at')
                ->with(['patient', 'doctor.user']);

            if ($user->role === 'medecin' && $user->doctor && ! $user->seesAllAppointments()) {
                $weekQuery->where('doctor_id', $user->doctor->id);
            } elseif ($user->role === 'medecin' && ! $user->doctor) {
                $weekQuery->whereRaw('1 = 0');
            }

            $allWeekAppointments = $weekQuery->get();

            foreach ($weekDays as $day) {
                $weekAppointments[$day->toDateString()] = $allWeekAppointments->filter(
                    fn ($a) => $a->scheduled_at->isSameDay($day)
                );
            }
        }

        return view('livewire.rdv', [
            'weekDays' => $weekDays,
            'weekAppointments' => $weekAppointments,
            'currentDay' => $selected,
            'today' => $dayAppointments,
            'todayCount' => $dayAppointmentsAll->count(),
            'pending' => $pending,
            'byDoctor' => $byDoctor,
            'myUnavailabilityToday' => $myUnavailabilityToday,
            'othersUnavailableToday' => $othersUnavailableToday,
            'unavailableDatesInWeek' => $unavailableDatesInWeek,
            'canConfirm' => in_array($user->role, ['admin', 'reception', 'medecin'], true),
            'canCreate' => $user->can('create', Appointment::class),
            'canSignalUnavailable' => in_array($user->role, ['medecin', 'admin'], true) && $user->doctor,
            'canDelete' => $user->isAdmin(),
        ])->layout('layouts.app', ['notifications' => collect()]);
    }
}
