<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use App\Livewire\Dashboard;
use App\Livewire\Rdv;
use App\Livewire\Stocks;
use App\Livewire\Patients;
use App\Livewire\Users;
use App\Livewire\Reports;
use App\Livewire\Profile;
use App\Livewire\PatientPortal\Register as PatientRegister;
use App\Livewire\PatientPortal\Login as PatientLogin;
use App\Livewire\PatientPortal\Dashboard as PatientDashboard;
use App\Livewire\Auth\Login;
use App\Models\Product;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\StockMovement;
use App\Models\Doctor;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/login', Login::class)->name('login')->middleware('guest');

    // ---------- Espace patiente ----------
    Route::get('/espace-patiente/inscription', PatientRegister::class)->name('patient.register')->middleware('guest:patient');
    Route::get('/espace-patiente/connexion', PatientLogin::class)->name('patient.login')->middleware('guest:patient');
    Route::get('/espace-patiente', PatientDashboard::class)->name('patient.dashboard')->middleware('auth:patient');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/rendez-vous', Rdv::class)->name('rdv');
        Route::get('/stocks', Stocks::class)->name('stocks');
        Route::get('/dossiers-patients', Patients::class)->name('patients');
        Route::get('/utilisateurs', Users::class)->name('users');
        Route::get('/mon-profil', Profile::class)->name('profile');
        Route::get('/rapports', Reports::class)->name('reports');

        Route::get('/dossiers-patients/modele-import', function () {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Patientes');

            $headers = [
                'Prénom', 'Nom', 'Téléphone', 'Date de naissance (JJ/MM/AAAA)', 'Sexe (F/M)',
                'Email', 'Adresse', 'Contact urgence - nom', 'Contact urgence - téléphone', 'Antécédents',
            ];
            foreach ($headers as $i => $h) {
                $sheet->setCellValue(chr(65 + $i) . '1', $h);
            }
            $sheet->getStyle('A1:J1')->getFont()->setBold(true);
            $sheet->getStyle('A1:J1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('241A15');
            $sheet->getStyle('A1:J1')->getFont()->getColor()->setRGB('FFFFFF');

            $sheet->setCellValue('A2', 'Danielle');
            $sheet->setCellValue('B2', 'Koffi');
            $sheet->setCellValue('C2', '0701000099');
            $sheet->setCellValue('D2', '15/03/1996');
            $sheet->setCellValue('E2', 'F');

            foreach (range('A', 'J') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, 'modele-import-patientes.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        })->name('patients.import-template');

        Route::get('/stocks/export/excel', function (\Illuminate\Http\Request $request) {
            $user = Auth::user();
            $domain = $request->query('domain');

            if (! $user->canReadStockDomain($domain)) {
                abort(403);
            }

            $domainLabel = match ($domain) {
                'pharmacie' => 'Pharmacie', 'consommable' => 'Consommables',
                'non_consommable' => 'Non consommables', 'cuisine' => 'Cuisine',
                default => ucfirst($domain),
            };

            $products = Product::whereHas('category', fn ($q) => $q->where('domain', $domain))
                ->with('category')->orderBy('name')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Stock');

            $sheet->setCellValue('A1', 'CLINIQUE FAME');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getFont()->getColor()->setRGB('C0410C');

            $sheet->setCellValue('A2', 'État du stock — ' . $domainLabel);
            $sheet->getStyle('A2')->getFont()->setSize(11);

            $sheet->setCellValue('A3', 'Exporté le ' . now()->format('d/m/Y à H:i') . ' par ' . $user->name);
            $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true);

            $headerRow = 5;
            $headers = ['Produit', 'Catégorie', 'Quantité', 'Unité', 'Seuil', 'Statut'];
            foreach ($headers as $i => $h) {
                $col = chr(65 + $i);
                $sheet->setCellValue($col . $headerRow, $h);
            }
            $sheet->getStyle('A' . $headerRow . ':F' . $headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A' . $headerRow . ':F' . $headerRow)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('241A15');

            $row = $headerRow + 1;
            foreach ($products as $p) {
                $sheet->setCellValue('A' . $row, $p->name);
                $sheet->setCellValue('B' . $row, $p->category->name);
                $sheet->setCellValue('C' . $row, $p->formattedQuantity());
                $sheet->setCellValue('D' . $row, $p->unit);
                $sheet->setCellValue('E' . $row, $p->alert_threshold);

                $statusLabel = $p->status() === 'rupture' ? 'Rupture' : ($p->status() === 'alerte' ? 'Stock bas' : 'Disponible');
                $sheet->setCellValue('F' . $row, $statusLabel);

                $color = match ($p->status()) {
                    'rupture' => 'F8E5E3',
                    'alerte' => 'FBF0DC',
                    default => 'E4EFEC',
                };
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);

                $row++;
            }

            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'stock-' . $domain . '-' . now()->format('Y-m-d') . '.xlsx';
            $writer = new Xlsx($spreadsheet);

            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        })->name('stocks.export.excel');

        Route::get('/stocks/export/pdf', function (\Illuminate\Http\Request $request) {
            $user = Auth::user();
            $domain = $request->query('domain');

            if (! $user->canReadStockDomain($domain)) {
                abort(403);
            }

            $domainLabel = match ($domain) {
                'pharmacie' => 'Pharmacie', 'consommable' => 'Consommables',
                'non_consommable' => 'Non consommables', 'cuisine' => 'Cuisine',
                default => ucfirst($domain),
            };

            $products = Product::whereHas('category', fn ($q) => $q->where('domain', $domain))
                ->with('category')->orderBy('name')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.stock-export', compact('products', 'domainLabel'));

            return $pdf->download('stock-' . $domain . '-' . now()->format('Y-m-d') . '.pdf');
        })->name('stocks.export.pdf');

        // ---------- Export du rapport (Excel / PDF) ----------

        $reportStats = function (\Illuminate\Http\Request $request) {
            $period = $request->query('period', 'month');
            $doctorId = $request->query('doctor') ?: null;
            $domain = $request->query('domain') ?: null;

            $bounds = match ($period) {
                'month' => [now()->startOfMonth(), now()->endOfMonth()],
                'year' => [now()->startOfYear(), now()->endOfYear()],
                default => [now()->subYears(10), now()],
            };
            [$start, $end] = $bounds;

            $newPatients = Patient::whereBetween('created_at', [$start, $end])->count();

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
                ->when($domain, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('domain', $domain)));
            $ruptureCount = $productScope()->outOfStock()->count();
            $lowStockCount = $productScope()->belowThreshold()->where('quantity_on_hand', '>', 0)->count();

            $byType = Consultation::whereBetween('consulted_at', [$start, $end])
                ->whereNotNull('consultation_type_id')
                ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
                ->selectRaw('consultation_type_id, count(*) as total')
                ->groupBy('consultation_type_id')
                ->with('type')
                ->get()
                ->sortByDesc('total');

            $byDoctor = collect();
            if (! $doctorId) {
                $byDoctor = Consultation::whereBetween('consulted_at', [$start, $end])
                    ->selectRaw('doctor_id, count(*) as total')
                    ->groupBy('doctor_id')
                    ->with('doctor.user')
                    ->get()
                    ->sortByDesc('total');
            }

            $periodLabel = match ($period) {
                'month' => 'Rapport du mois — ' . now()->translatedFormat('F Y'),
                'year' => 'Rapport de l\'année ' . now()->format('Y'),
                default => 'Rapport complet — depuis le début',
            };

            $doctorName = $doctorId ? (Doctor::with('user')->find($doctorId)?->user?->name) : null;
            $domainLabel = $domain ? match ($domain) {
                'pharmacie' => 'Pharmacie', 'consommable' => 'Consommables',
                'non_consommable' => 'Non consommables', 'cuisine' => 'Cuisine',
                default => ucfirst($domain),
            } : null;

            return compact(
                'newPatients', 'consultationsTotal', 'apptTotal', 'apptConfirmed',
                'cancelRate', 'ruptureCount', 'lowStockCount', 'byType', 'byDoctor',
                'periodLabel', 'doctorName', 'domainLabel'
            );
        };

        Route::get('/rapports/export/excel', function (\Illuminate\Http\Request $request) use ($reportStats) {
            if (! Auth::user()->isAdmin()) abort(403);

            $s = $reportStats($request);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Rapport');

            $sheet->setCellValue('A1', 'CLINIQUE FAME');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getFont()->getColor()->setRGB('C0410C');
            $sheet->setCellValue('A2', $s['periodLabel']);
            $sheet->setCellValue('A3', 'Exporté le ' . now()->format('d/m/Y à H:i'));
            $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true);

            $sheet->setCellValue('A5', 'Nouvelles patientes'); $sheet->setCellValue('B5', $s['newPatients']);
            $sheet->setCellValue('A6', 'Consultations'); $sheet->setCellValue('B6', $s['consultationsTotal']);
            $sheet->setCellValue('A7', 'RDV confirmés'); $sheet->setCellValue('B7', $s['apptConfirmed'] . '/' . $s['apptTotal']);
            $sheet->setCellValue('A8', "Taux d'annulation"); $sheet->setCellValue('B8', $s['cancelRate'] . '%');
            $sheet->setCellValue('A9', 'Produits en rupture'); $sheet->setCellValue('B9', $s['ruptureCount']);
            $sheet->setCellValue('A10', 'Produits en alerte'); $sheet->setCellValue('B10', $s['lowStockCount']);
            $sheet->getStyle('A5:A10')->getFont()->setBold(true);

            $row = 13;
            $sheet->setCellValue('A' . $row, 'Consultations par type');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;
            $sheet->setCellValue('A' . $row, 'Code'); $sheet->setCellValue('B' . $row, 'Type'); $sheet->setCellValue('C' . $row, 'Total');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('241A15');
            $row++;
            foreach ($s['byType'] as $t) {
                $sheet->setCellValue('A' . $row, $t->type->code ?? '?');
                $sheet->setCellValue('B' . $row, $t->type->label ?? 'Type supprimé');
                $sheet->setCellValue('C' . $row, $t->total);
                $row++;
            }

            if ($s['byDoctor']->isNotEmpty()) {
                $row += 2;
                $sheet->setCellValue('A' . $row, 'Consultations par médecin');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
                $row++;
                $sheet->setCellValue('A' . $row, 'Médecin'); $sheet->setCellValue('B' . $row, 'Total');
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('241A15');
                $row++;
                foreach ($s['byDoctor'] as $d) {
                    $sheet->setCellValue('A' . $row, $d->doctor->user->name ?? 'Médecin supprimé');
                    $sheet->setCellValue('B' . $row, $d->total);
                    $row++;
                }
            }

            foreach (range('A', 'C') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            return response()->streamDownload(function () use ($writer) {
                $writer->save('php://output');
            }, 'rapport-' . now()->format('Y-m-d') . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        })->name('reports.export.excel');

        Route::get('/rapports/export/pdf', function (\Illuminate\Http\Request $request) use ($reportStats) {
            if (! Auth::user()->isAdmin()) abort(403);

            $s = $reportStats($request);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', $s);

            return $pdf->download('rapport-' . now()->format('Y-m-d') . '.pdf');
        })->name('reports.export.pdf');
    });
});
