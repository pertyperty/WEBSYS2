<?php

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
});
