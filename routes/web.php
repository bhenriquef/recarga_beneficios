<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;

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
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{id}/report/pdf', [EmployeeController::class, 'gerarRelatorioPDF'])->name('employees.report.pdf');



    Route::get('/exports/generate', [ExportController::class, 'generate']);
    Route::get('/exports/check', [ExportController::class, 'check'])->name('exports.check');
    Route::get('/exports/download/{type}', [ExportController::class, 'download'])->name('exports.download');

    Route::resource('users', \App\Http\Controllers\UserController::class);

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

require __DIR__.'/auth.php';
