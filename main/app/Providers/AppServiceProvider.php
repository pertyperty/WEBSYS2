<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('manage-users', fn (User $user): bool => $user->is_active && $user->role === UserRole::Admin);
        Gate::define('manage-organization', fn (User $user): bool => $user->is_active && $user->role === UserRole::Admin);
        Gate::define('manage-requirements', fn (User $user): bool => $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::ComplianceOfficer], true));
        Gate::define('view-employees', fn (User $user): bool => $user->is_active && $user->role !== UserRole::Auditor);
        Gate::define('view-training', fn (User $user): bool => $user->is_active && $user->role !== UserRole::Auditor);
        Gate::define('manage-courses', fn (User $user): bool => $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::ComplianceOfficer], true));
        Gate::define('enroll-training', fn (User $user): bool => $user->is_active && $user->isAssignedStaff());
        Gate::define('complete-training', fn (User $user): bool => $user->is_active && $user->role === UserRole::ComplianceOfficer);
        Gate::define('view-credentials', fn (User $user): bool => $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::ComplianceOfficer, UserRole::Staff], true));
        Gate::define('submit-credentials', fn (User $user): bool => $user->is_active && $user->isAssignedStaff());
        Gate::define('review-credentials', fn (User $user): bool => $user->is_active && $user->role === UserRole::ComplianceOfficer);
        Password::defaults(fn (): Password => Password::min(12)->letters()->numbers());
    }
}
