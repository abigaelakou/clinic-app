<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $domains = $user->readableStockDomains();

        // ---- Stocks : uniquement les domaines que ce rôle a le droit de voir ----
        $stockAlertsQuery = Product::belowThreshold()->with('category')
            ->whereHas('category', fn ($q) => $q->whereIn('domain', $domains));

        $stockAlerts = (clone $stockAlertsQuery)->limit(4)->get();
        $alertsCount = (clone $stockAlertsQuery)->count();

        // ---- Rendez-vous : tous, ou seulement les siens s'il est médecin ----
        $appointmentsQuery = Appointment::today()->orderBy('scheduled_at')->with(['patient', 'doctor.user']);

        if (! $user->canAccessAppointments()) {
            $appointmentsQuery->whereRaw('1 = 0'); // aucun accès à ce module
        } elseif (! $user->seesAllAppointments() && $user->role === 'medecin' && $user->doctor) {
            $appointmentsQuery->where('doctor_id', $user->doctor->id);
        } elseif ($user->role === 'medecin' && ! $user->doctor) {
            $appointmentsQuery->whereRaw('1 = 0'); // compte médecin non rattaché à une fiche Doctor
        }

        $todayAppointments = $appointmentsQuery->get();

        $pendingRequests = $user->canAccessAppointments()
            ? Appointment::pending()->with('patient')
                ->when($user->role === 'medecin' && $user->doctor, fn ($q) => $q->where('doctor_id', $user->doctor->id))
                ->when(! $user->seesAllAppointments() && $user->role !== 'medecin' && ! $user->isAdmin(), fn ($q) => $q->whereRaw('1 = 0'))
                ->limit(3)->get()
            : collect();

        // ---- Notifications : mêmes règles que ci-dessus ----
        $notifications = collect();

        foreach ($stockAlerts as $product) {
            $notifications->push([
                'type' => $product->status() === 'rupture' ? 'crit' : 'warn',
                'icon' => $product->status() === 'rupture' ? '⛔' : '⚠',
                'title' => ($product->status() === 'rupture' ? 'Rupture — ' : 'Stock bas — ') . $product->name,
                'desc' => 'Quantité actuelle : ' . $product->quantity_on_hand,
                'time' => '',
            ]);
        }

        foreach ($pendingRequests as $appt) {
            $notifications->push([
                'type' => 'info',
                'icon' => '📅',
                'title' => 'Demande de rendez-vous',
                'desc' => ($appt->patient->first_name ?? 'Une patiente') . ' — ' . $appt->scheduled_at->format('d/m à H:i'),
                'time' => $appt->created_at->diffForHumans(),
            ]);
        }

        return view('livewire.dashboard', [
            'alertsCount' => $alertsCount,
            'appointmentsToday' => $todayAppointments->count(),
            'appointmentsPending' => $todayAppointments->where('status', 'pending')->count(),
            'stockAlerts' => $stockAlerts,
            'todayAppointments' => $todayAppointments,
            'canSeeStock' => count($domains) > 0,
            'canSeeAppointments' => $user->canAccessAppointments(),
        ])->layout('layouts.app', ['notifications' => $notifications->take(6)]);
    }
}
