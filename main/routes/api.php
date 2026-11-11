<?php

use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Middleware\EnsureAuditorToken;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', EnsureAuditorToken::class, 'throttle:verifications'])->group(function (): void {
    Route::get('/verifications/{reference}', VerificationController::class)->name('api.v1.verifications.show');
});
