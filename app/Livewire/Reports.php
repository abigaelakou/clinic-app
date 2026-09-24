<?php

namespace App\Livewire;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Reports extends Component
{
    public string $period = 'month'; // 'month' | 'year' | 'all'
    public ?int $filterDoctorId = null;
    public string $filterStockDomain = '';

    public function mount()
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, "Cette page est réservée à l'administrateur.");
        }
    }

    public function setPeriod(string $period)
    {
        $this->period = $period;
    }

    protected function periodBounds(int $offset = 0): array
    {
        return match ($this->period) {
            'month' => [
                now()->subMonthsNoOverflow($offset)->startOfMonth(),
                now()->subMonthsNoOverflow($offset)->endOfMonth(),
            ],
            'year' => [
                now()->subYears($offset)->startOfYear(),
                now()->subYears($offset)->endOfYear(),
            ],
            default => [now()->subYears(10), now()],
        };
    }

    /** Variation en % entre deux valeurs, null si non comparable (période "Depuis toujours"). */
    protected function pctChange(?float $current, ?float $previous): ?float
    {
        if ($this->period === 'all' || $previous === null) return null;
        if ($previous == 0) return $current > 0 ? 100.0 : 0.0;
        return round(($current - $previous) / $previous * 100);
    }

    protected function computeStats(array $bounds, ?int $doctorId, string $stockDomain): array
    {
        [$start, $end] = $bounds;

        $newPatients = \App\Models\Patient::whereBetween('created_at', [$start, $end])->count();

        $consultQuery = fn () => Consultation::whereBetween('consulted_at', [$start, $end])
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId));
        $consultationsTotal = $consultQuery()->count();

        $apptQuery = fn () => Appointment::whereBetween('created_at', [$start, $end])
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId));
        $apptTotal = $apptQuery()->count();
        $apptConfirmed = $apptQuery()->whereIn('status', ['confirmed', 'completed'])->count();
        $apptCancelled = $apptQuery()->where('status', 'cancelled')->count();
        $cancelRate = $apptTotal > 0 ? round($apptCancelled / $apptTotal * 100) : 0;

        $productScope = fn () => Product::where('is_active', true)
            ->when($stockDomain, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('domain', $stockDomain)));
        $ruptureCount = $productScope()->outOfStock()->count();
        $lowStockCount = $productScope()->belowThreshold()->where('quantity_on_hand', '>', 0)->count();

        $movementScope = function ($type) use ($start, $end, $stockDomain) {
            $query = StockMovement::whereBetween('created_at', [$start, $end])->where('type', $type);

            if ($stockDomain) {
                $productIds = Product::whereHas('category', fn ($c) => $c->where('domain', $stockDomain))->pluck('id');
                $query->whereIn('product_id', $productIds);
            }

            return $query;
        };
        $entries = $movementScope('entry')->count();
        $exits = $movementScope('exit')->count();

        return compact(
            'newPatients', 'consultationsTotal', 'apptTotal',
            'apptConfirmed', 'apptCancelled', 'cancelRate',
            'ruptureCount', 'lowStockCount', 'entries', 'exits'
        );
    }

    public function render()
    {
        $current = $this->computeStats($this->periodBounds(0), $this->filterDoctorId, $this->filterStockDomain);
        $previous = $this->period === 'all' ? null : $this->computeStats($this->periodBounds(1), $this->filterDoctorId, $this->filterStockDomain);

        $deltas = [];
        foreach (['newPatients', 'consultationsTotal', 'apptConfirmed', 'cancelRate'] as $key) {
            $deltas[$key] = $this->pctChange($current[$key], $previous[$key] ?? null);
        }

        [$start, $end] = $this->periodBounds(0);

        // ---- Consultations par type ----
        $byType = Consultation::whereBetween('consulted_at', [$start, $end])
            ->whereNotNull('consultation_type_id')
            ->when($this->filterDoctorId, fn ($q) => $q->where('doctor_id', $this->filterDoctorId))
            ->selectRaw('consultation_type_id, count(*) as total')
            ->groupBy('consultation_type_id')
            ->with('type')
            ->get()
            ->sortByDesc('total');

        $noTypeCount = Consultation::whereBetween('consulted_at', [$start, $end])
            ->whereNull('consultation_type_id')
            ->when($this->filterDoctorId, fn ($q) => $q->where('doctor_id', $this->filterDoctorId))
            ->count();
        $maxTypeCount = $byType->max('total') ?: 1;

        // ---- Consultations par médecin (masqué si on filtre déjà sur un médecin précis) ----
        $byDoctor = collect();
        $maxDoctorCount = 1;
        if (! $this->filterDoctorId) {
            $byDoctor = Consultation::whereBetween('consulted_at', [$start, $end])
                ->selectRaw('doctor_id, count(*) as total')
                ->groupBy('doctor_id')
                ->with('doctor.user')
                ->get()
                ->sortByDesc('total');
            $maxDoctorCount = $byDoctor->max('total') ?: 1;
        }

        // ---- Évolution des consultations sur les 6 derniers mois ----
        $evolution = collect(range(5, 0))->map(function ($i) {
            $monthStart = now()->subMonthsNoOverflow($i)->startOfMonth();
            $monthEnd = now()->subMonthsNoOverflow($i)->endOfMonth();
            return [
                'label' => $monthStart->translatedFormat('M'),
                'total' => Consultation::whereBetween('consulted_at', [$monthStart, $monthEnd])
                    ->when($this->filterDoctorId, fn ($q) => $q->where('doctor_id', $this->filterDoctorId))
                    ->count(),
            ];
        });
        $maxEvolution = $evolution->max('total') ?: 1;

        return view('livewire.reports', array_merge($current, [
            'deltas' => $deltas,
            'byType' => $byType,
            'noTypeCount' => $noTypeCount,
            'maxTypeCount' => $maxTypeCount,
            'byDoctor' => $byDoctor,
            'maxDoctorCount' => $maxDoctorCount,
            'evolution' => $evolution,
            'maxEvolution' => $maxEvolution,
            'filteredDoctors' => Doctor::with('user')->orderBy('id')->get(),
        ]))->layout('layouts.app', ['notifications' => collect()]);
    }
}
