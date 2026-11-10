<?php

namespace App\Http\Controllers;

use App\Enums\TrainingStatus;
use App\Http\Requests\CompleteTrainingRequest;
use App\Models\EmployeeTraining;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TrainingCompletionController extends Controller
{
    public function update(CompleteTrainingRequest $request, EmployeeTraining $enrollment): RedirectResponse
    {
        DB::transaction(function () use ($request, $enrollment): void {
            $locked = EmployeeTraining::lockForUpdate()->findOrFail($enrollment->id);
            if ($locked->employee_id === $request->user()->id) {
                throw ValidationException::withMessages(['completed_at' => 'You cannot record your own training completion.']);
            }
            if ($locked->status !== TrainingStatus::Enrolled) {
                throw ValidationException::withMessages(['completed_at' => 'Completion is already recorded and cannot be overwritten.']);
            }
            $locked->forceFill([
                'status' => TrainingStatus::Completed,
                'completed_at' => $request->validated('completed_at'),
                'feedback' => $request->validated('feedback'),
                'completed_by' => $request->user()->id,
            ])->save();
        });

        return to_route('training-enrollments.index')->with('status', 'Completion recorded. Approved evidence is still required for compliance.');
    }
}
