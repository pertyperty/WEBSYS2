<?php

use App\Http\Controllers\Api\V1\EmployeeComplianceController;
use App\Http\Controllers\Api\V1\FacilityComplianceController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Middleware\EnsureAuditorToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function (): void {
    Route::middleware([EnsureAuditorToken::class, 'throttle:verifications'])->group(function (): void {
        Route::get('/verifications/{reference}', VerificationController::class)->name('api.v1.verifications.show');
    });
    Route::middleware([EnsureAuditorToken::class.':compliance:read', 'throttle:verifications'])->group(function (): void {
        Route::get('/employees/{reference}/compliance', EmployeeComplianceController::class)->name('api.v1.employees.compliance');
        Route::get('/facilities/{facility}/compliance', FacilityComplianceController::class)->whereNumber('facility')->name('api.v1.facilities.compliance');
    });
});
