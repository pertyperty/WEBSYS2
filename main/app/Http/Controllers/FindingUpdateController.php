<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreFindingUpdateRequest;
use App\Models\AuditFinding;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FindingUpdateController extends Controller
{
    public function store(StoreFindingUpdateRequest $request, int $finding): RedirectResponse
    {
        DB::transaction(function () use ($request, $finding): void {
            $actor = User::lockForUpdate()->findOrFail($request->user()->id);
            $data = $request->validated();
            $manager = empty($data['assignee_id']) ? null : User::lockForUpdate()->findOrFail($data['assignee_id']);
            $finding = AuditFinding::visibleTo($actor)->lockForUpdate()->findOrFail($finding);
            abort_unless($finding->canReceiveUpdateFrom($actor), 403);
            if ($finding->status === 'resolved' && $actor->role !== UserRole::ComplianceOfficer) {
                throw ValidationException::withMessages(['status' => 'This finding is resolved. Ask a compliance officer to reopen it.']);
            }
            if ($manager && (! $manager->is_active || $manager->role !== UserRole::FacilityManager || $manager->facility_id !== $finding->facility_id)) {
                throw ValidationException::withMessages(['assignee_id' => 'Choose a current manager in this facility.']);
            }
            if ($data['status'] === 'resolved' && ($finding->status !== 'pending_verification' || $actor->id === $finding->assignee_id || $actor->id === $finding->updates()->reorder()->latest('id')->first()?->author_id)) {
                throw ValidationException::withMessages(['status' => 'Resolve only submitted corrective work verified by a separate officer.']);
            }
            $finding->updates()->create(['status' => $data['status'], 'note' => $data['note'], 'author_id' => $actor->id, 'assignee_name' => $manager?->name]);
            $finding->forceFill(['assignee_id' => $manager?->id ?? $finding->assignee_id, 'status' => $data['status'], 'resolved_at' => $data['status'] === 'resolved' ? now() : null])->save();
        });

        return to_route('findings.show', $finding)->with('status', 'Corrective update saved.');
    }
}
