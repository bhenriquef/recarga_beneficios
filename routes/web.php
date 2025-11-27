<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BalanceManagementImportController;
use Illuminate\Support\Facades\Cache;

Route::get('/', function () {
    return view('auth.login');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/imports/upload', [ImportController::class, 'upload'])->name('imports.upload');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/{company_id}', [CompanyController::class, 'show'])->name('companies.show');

    Route::get('/benefits', [BenefitController::class, 'index'])->name('benefits.index');
    Route::get('/benefits/{benefit_id}', [BenefitController::class, 'show'])->name('benefits.show');

    // routes/web.php
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/show/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    // Route::get('/employees/{id}/report/pdf', [EmployeeController::class, 'gerarRelatorioPDF'])->name('employees.report.pdf');
    Route::get('/employees/filter', [EmployeeController::class, 'filter'])->name('employees.filter');

    Route::get('/exports/generate', [ExportController::class, 'generate']);
    Route::get('/exports/check', [ExportController::class, 'check'])->name('exports.check');
    Route::get('/exports/download/{type}', [ExportController::class, 'download'])->name('exports.download');
    Route::get('/excel_customizado', [ExportController::class, 'indexCustomExport'])->name('excelCustomizado');
    Route::get('/exports/generate_custom_ifood', [ExportController::class, 'generateCustomIfoodExcel'])->name('exports.generateCustomIfoodExcel');

    Route::get('/balance-management/import', [BalanceManagementImportController::class, 'index'])->name('balance.import');
    Route::post('/balance-management/import', [BalanceManagementImportController::class, 'store'])->name('balance.import.store');

    Route::post('/sync-database', [ImportController::class, 'runSyncDatabase'])->name('database.sync');
    Route::get('/sync-database-stream', function () {
        return response()->stream(function () {
            // evita timeouts/encerramento antecipado pelo PHP
            ignore_user_abort(true);
            set_time_limit(0);

            // limpa buffers de output que possam travar o flush no PHP-FPM/nginx
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            // envia um byte inicial para abrir o stream no navegador
            echo ":\n\n";
            flush();

            while (true) {
                $progress = Cache::get('sync_progress', 0);
                $logs     = Cache::get('sync_logs', []);
                $eta      = Cache::get('sync_eta');
                $finished = Cache::get('sync_finished', false);
                $error    = Cache::get('sync_error');

                echo "data: " . json_encode([
                    'progress' => $progress,
                    'log'      => array_shift($logs),
                    'eta'      => $eta,
                    'finished' => $finished || $progress >= 100,
                    'error'    => $error,
                ]) . "\n\n";

                flush();

                Cache::put('sync_logs', $logs);

                if (($finished || $progress >= 100 || $error) && empty($logs)) {
                    break;
                }

                usleep(300000); // 0.3s
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no', // desliga buffer do nginx
            'Connection'        => 'keep-alive',
        ]);
    });


    Route::resource('users', \App\Http\Controllers\UserController::class);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
