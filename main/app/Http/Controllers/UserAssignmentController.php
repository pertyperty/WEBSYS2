<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserAssignmentRequest;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserAssignmentController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::orderBy('name')->paginate(15),
            'roles' => UserRole::cases(),
            'facilities' => Facility::orderBy('name')->get(),
            'jobs' => JobRole::orderBy('name')->get(),
            'regions' => Region::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserAssignmentRequest $request, User $user): RedirectResponse
    {
        $user->forceFill(array_merge([
            'facility_id' => null,
            'job_role_id' => null,
            'region_id' => null,
        ], $request->validated()))->save();

        return back()->with('status', 'Account assignment updated.');
    }
}
