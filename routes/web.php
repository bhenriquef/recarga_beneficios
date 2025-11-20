<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
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

    Route::post('/sync-database', [ImportController::class, 'runSyncDatabase'])->name('database.sync');
    Route::get('/sync-database-stream', function () {
        return response()->stream(function () {
            while (true) {
                $progress = Cache::get('sync_progress', 0);
                $logs = Cache::get('sync_logs', []);

                $eta = Cache::get('sync_eta');

                echo "data: " . json_encode([
                    'progress' => $progress,
                    'log'      => array_shift($logs),
                    'eta'      => $eta,
                    'finished' => $progress >= 100
                ]) . "\n\n";


                ob_flush(); flush();

                Cache::put('sync_logs', $logs);

                if ($progress >= 100 && empty($logs)) {
                    break;
                }

                usleep(300000); // 0.3s
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no'
        ]);
    });


    Route::resource('users', \App\Http\Controllers\UserController::class);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php';
