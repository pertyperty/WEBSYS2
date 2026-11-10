<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\JobRequirementController;
use App\Http\Controllers\JobRoleController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SafetyDomainController;
use App\Http\Controllers\UserAssignmentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::view('/awaiting-assignment', 'onboarding')->name('onboarding');
    Route::view('/profile', 'profile')->name('profile');
    Route::view('/dashboard', 'dashboard')->middleware('assigned')->name('dashboard');

    Route::middleware('can:manage-users')->group(function () {
        Route::get('/admin/users', [UserAssignmentController::class, 'index'])->name('admin.users');
        Route::patch('/admin/users/{user}/assignment', [UserAssignmentController::class, 'update'])->name('admin.users.assignment');
    });

    Route::middleware('can:manage-organization')->group(function () {
        Route::resource('regions', RegionController::class)->except(['show', 'destroy']);
        Route::resource('facilities', FacilityController::class)->except(['show', 'destroy']);
        Route::resource('safety-domains', SafetyDomainController::class)->parameters(['safety-domains' => 'safetyDomain'])->except(['show', 'destroy']);
        Route::resource('qualifications', QualificationController::class)->except(['show', 'destroy']);
        Route::resource('job-roles', JobRoleController::class)->parameters(['job-roles' => 'jobRole'])->except(['index', 'show', 'destroy']);
    });

    Route::middleware('can:manage-requirements')->group(function () {
        Route::get('/job-roles', [JobRoleController::class, 'index'])->name('job-roles.index');
        Route::get('/job-roles/{jobRole}/requirements', [JobRequirementController::class, 'edit'])->name('job-roles.requirements.edit');
        Route::put('/job-roles/{jobRole}/requirements', [JobRequirementController::class, 'update'])->name('job-roles.requirements.update');
    });

    Route::middleware(['assigned', 'can:view-employees'])->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->whereNumber('employee')->name('employees.show');
    });
});
