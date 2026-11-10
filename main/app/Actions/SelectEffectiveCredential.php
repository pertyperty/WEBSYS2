<?php

namespace App\Actions;

use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Models\CredentialDocument;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class SelectEffectiveCredential
{
    public function find(User $employee, Qualification $qualification): ?CredentialDocument
    {
        if (! $qualification->is_active || ! $qualification->safetyDomain->is_active) {
            return null;
        }
        $today = now('Asia/Manila')->toDateString();
        $query = CredentialDocument::where('employee_id', $employee->id)->where('qualification_id', $qualification->id)
            ->where('status', CredentialStatus::Approved)->where('issued_at', '<=', $today)
            ->where(function (Builder $dates) use ($qualification, $today): void {
                $dates->where('expires_at', '>=', $today);
                if (! $qualification->requires_expiry) {
                    $dates->orWhereNull('expires_at');
                }
            });
        if ($qualification->requires_training) {
            $query->whereHas('employeeTraining', function (Builder $enrollments) use ($employee, $qualification, $today): void {
                $enrollments->where('employee_id', $employee->id)->where('status', TrainingStatus::Completed)
                    ->whereNotNull('completed_at')->where('completed_at', '<=', $today)
                    ->whereHas('trainingCourse', fn (Builder $courses): Builder => $courses->where('qualification_id', $qualification->id));
            });
        }

        return $query->orderByDesc('issued_at')->orderByDesc('id')->first();
    }
}
