<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View as ViewInstance;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

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
        Gate::define('view-audits', fn (User $user): bool => $user->is_active && in_array($user->role, [UserRole::Admin, UserRole::ComplianceOfficer, UserRole::RegionalDirector, UserRole::FacilityManager], true));
        Gate::define('conduct-audits', fn (User $user): bool => $user->is_active && $user->role === UserRole::ComplianceOfficer);
        Gate::define('view-reports', fn (User $user): bool => $user->can('view-audits'));
        Gate::define('view-notifications', fn (User $user): bool => $user->is_active && ($user->isAssignedStaff() || ($user->role === UserRole::FacilityManager && $user->facility_id !== null)));
        View::composer('components.layout', function (ViewInstance $view): void {
            $user = auth()->user();
            $count = $user?->can('view-notifications') ? $user->unreadNotifications()->where('type', 'qualification_expiry')
                ->whereIn('data->employee_id', User::visibleTo($user)->select('id'))->count() : 0;
            $view->with('unreadAlertCount', $count);
        });
        Sanctum::authenticateAccessTokensUsing(fn (PersonalAccessToken $token, bool $valid): bool => $valid
            && $token->expires_at !== null && $token->expires_at->isFuture());
        RateLimiter::for('api-ip', fn (Request $request): Limit => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('verifications', fn (Request $request): Limit => Limit::perMinute(60)->by((string) $request->user()->id));
        Password::defaults(fn (): Password => Password::min(12)->letters()->numbers());
    }
}
