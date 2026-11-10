<?php

use App\Http\Controllers\CredentialDocumentController;
use App\Http\Controllers\CredentialDownloadController;
use App\Http\Controllers\CredentialReviewController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\JobRequirementController;
use App\Http\Controllers\JobRoleController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SafetyDomainController;
use App\Http\Controllers\TrainingCompletionController;
use App\Http\Controllers\TrainingCourseController;
use App\Http\Controllers\TrainingEnrollmentController;
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

    Route::middleware('can:manage-courses')->group(function () {
        Route::resource('training-courses', TrainingCourseController::class)->parameters(['training-courses' => 'course'])->except(['index', 'show', 'destroy']);
    });

    Route::middleware(['assigned', 'can:view-training'])->group(function () {
        Route::get('/training-courses', [TrainingCourseController::class, 'index'])->name('training-courses.index');
        Route::get('/training-courses/{course}', [TrainingCourseController::class, 'show'])->whereNumber('course')->name('training-courses.show');
        Route::get('/training-enrollments', [TrainingEnrollmentController::class, 'index'])->name('training-enrollments.index');
    });
    Route::post('/training-courses/{course}/enrollments', [TrainingEnrollmentController::class, 'store'])
        ->middleware('can:enroll-training')->name('training-enrollments.store');
    Route::patch('/training-enrollments/{enrollment}/completion', [TrainingCompletionController::class, 'update'])
        ->middleware('can:complete-training')->name('training-completions.update');

    Route::middleware(['assigned', 'can:submit-credentials'])->group(function () {
        Route::get('/credentials/create', [CredentialDocumentController::class, 'create'])->name('credentials.create');
        Route::post('/credentials', [CredentialDocumentController::class, 'store'])->name('credentials.store');
    });
    Route::middleware(['assigned', 'can:view-credentials'])->group(function () {
        Route::get('/credentials', [CredentialDocumentController::class, 'index'])->name('credentials.index');
        Route::get('/credentials/{document}', [CredentialDocumentController::class, 'show'])->whereNumber('document')->name('credentials.show');
        Route::get('/credentials/{document}/download', CredentialDownloadController::class)->whereNumber('document')->name('credentials.download');
    });
    Route::post('/credentials/{document}/reviews', [CredentialReviewController::class, 'store'])
        ->middleware('can:review-credentials')->name('credentials.reviews.store');
});
