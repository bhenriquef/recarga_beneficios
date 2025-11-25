<?php

use App\Http\Controllers\Api\EmployeeBenefitsController;
use App\Http\Middleware\ApiAccessToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', ApiAccessToken::class, 'throttle:30,1'])->group(function () {
    Route::post('/employees/benefits', EmployeeBenefitsController::class);
});
