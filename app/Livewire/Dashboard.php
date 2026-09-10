<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Product;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $todayAppointments = Appointment::today()->orderBy('scheduled_at')->with(['patient', 'doctor.user'])->get();
        $stockAlerts = Product::belowThreshold()->with('category')->limit(4)->get();
        $pendingRequests = Appointment::pending()->with('patient')->limit(3)->get();

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
            'alertsCount' => Product::belowThreshold()->count(),
            'appointmentsToday' => $todayAppointments->count(),
            'appointmentsPending' => $todayAppointments->where('status', 'pending')->count(),
            'stockAlerts' => $stockAlerts,
            'todayAppointments' => $todayAppointments,
        ])->layout('layouts.app', ['notifications' => $notifications->take(6)]);
    }
}
