<?php

namespace App\Http\Controllers;

use App\Actions\RecordComplianceAssessment;
use App\Enums\UserRole;
use App\Http\Requests\UpdateUserAssignmentRequest;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserAssignmentController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::orderBy('name')->orderBy('id')->paginate(15),
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
            $locked->forceFill(array_merge([
                'facility_id' => null,
                'job_role_id' => null,
                'region_id' => null,
            ], $request->validated()))->save();
            if ($wasStaff || $locked->role === UserRole::Staff) {
                $history->handle($locked, 'assignment', $request->user());
            }
        });

        return back()->with('status', 'Account assignment updated.');
    }
}
