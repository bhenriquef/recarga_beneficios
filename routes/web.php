<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BenefitController;
use App\Http\Controllers\ExportController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/imports/upload', [ImportController::class, 'upload'])->name('imports.upload');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/benefits', [BenefitController::class, 'index'])->name('benefits.index');

    Route::get('/exports/generate', [ExportController::class, 'generate']);
    Route::get('/exports/check', [ExportController::class, 'check'])->name('exports.check');
    Route::get('/exports/download/{type}', [ExportController::class, 'download'])->name('exports.download');
});

require __DIR__.'/auth.php';
