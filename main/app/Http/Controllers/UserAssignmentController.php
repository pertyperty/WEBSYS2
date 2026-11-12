<?php

namespace App\Http\Controllers;

use App\Actions\RecordComplianceAssessment;
use App\Enums\UserRole;
use App\Http\Requests\UpdateUserAssignmentRequest;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'active' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);
        $search = '%'.addcslashes($filters['search'] ?? '', '\\%_').'%';

        return view('admin.users', [
            'users' => User::query()->when($search !== '%%', fn (Builder $query): Builder => $query
                ->where(fn (Builder $names): Builder => $names->where('name', 'like', $search)->orWhere('email', 'like', $search)))
                ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
                ->when($filters['active'] ?? null, fn (Builder $query, string $active): Builder => $query->where('is_active', $active === 'active'))
                ->orderBy('name')->orderBy('id')->paginate(15)->withQueryString(),
            'roles' => UserRole::cases(),
            'facilities' => Facility::active()->orderBy('name')->get(),
            'jobs' => JobRole::active()->orderBy('name')->get(),
            'regions' => Region::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserAssignmentRequest $request, User $user, RecordComplianceAssessment $history): RedirectResponse
    {
        DB::transaction(function () use ($user, $request, $history): void {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $wasStaff = $locked->role === UserRole::Staff;
            $oldRole = $locked->role;
            $oldRegion = $locked->region_id;
            $locked->forceFill(array_merge([
                'facility_id' => null,
                'job_role_id' => null,
                'region_id' => null,
            ], $request->validated()))->save();
            if (! $locked->is_active || $oldRole !== $locked->role || (int) $oldRegion !== (int) $locked->region_id) {
                $locked->tokens()->delete();
            }
            if ($wasStaff || $locked->role === UserRole::Staff) {
                $history->handle($locked, 'assignment', $request->user());
            }
        });

        return back()->with('status', 'Account assignment updated.');
    }
}
