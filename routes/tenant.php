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
use App\Livewire\Auth\Login;
use App\Models\Product;
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

            // Une ligne d'exemple pour guider la saisie.
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
    });
});
