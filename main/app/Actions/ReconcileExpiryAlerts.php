<?php

namespace App\Actions;

use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\CredentialDocument;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReconcileExpiryAlerts
{
    public function __construct(private AssessEmployeeCompliance $assessment, private SelectEffectiveCredential $selection) {}

    public function handle(User $employee, CarbonImmutable $asOf): void
    {
        DB::transaction(function () use ($employee, $asOf): void {
            $locked = User::lockForUpdate()->findOrFail($employee->id);
            $events = $this->currentEvents($locked, $asOf);
            $recipients = User::where('is_active', true)->where(function ($query) use ($locked): void {
                $query->whereKey($locked->id)->orWhere(fn ($managers) => $managers->where('role', UserRole::FacilityManager)->where('facility_id', $locked->facility_id));
            })->get();
            foreach ($events as $event) {
                foreach ($recipients as $recipient) {
                    $key = implode(':', [$recipient->id, $locked->id, $event['qualification_id'], $event['document_id'], $event['event']]);
                    $hex = hash('sha256', $key);
                    $id = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-5'.substr($hex, 13, 3).'-a'.substr($hex, 17, 3).'-'.substr($hex, 20, 12);
                    $recipient->notifications()->firstOrCreate(['id' => $id], ['type' => 'qualification_expiry', 'data' => $event]);
                }
            }
        });
    }

    /** @return array<int, array{employee_id: int, employee_name: string, qualification_id: int, qualification_name: string, document_id: int, expires_at: string, event: string}> */
    public function currentEvents(User $employee, ?CarbonImmutable $asOf = null): array
    {
        $asOf = ($asOf ?? CarbonImmutable::now('Asia/Manila'))->setTimezone('Asia/Manila')->startOfDay();
        $result = $this->assessment->handle($employee, $asOf);
        if ($result['status'] === 'not_assessable') {
            return [];
        }
        $events = [];
        foreach ($result['items'] as $item) {
            $document = null;
            $event = null;
            if ($item['expiring_soon']) {
                $document = $employee->credentialDocuments->firstWhere('id', $item['document_id']);
                $event = 'expiring_soon';
            } elseif ($item['document_id'] === null) {
                $qualification = $employee->jobRole->qualifications->firstWhere('id', $item['qualification_id']);
                $document = $employee->credentialDocuments->filter(fn (CredentialDocument $candidate): bool => $candidate->qualification_id === $qualification->id
                    && $candidate->status === CredentialStatus::Approved && $candidate->expires_at !== null
                    && $candidate->expires_at->toDateString() < $asOf->toDateString()
                    && $this->selection->isQualifying($candidate, $qualification, CarbonImmutable::parse($candidate->expires_at->toDateString(), 'Asia/Manila')))
                    ->sortBy([['expires_at', 'desc'], ['id', 'desc']])->first();
                $event = 'expired';
            }
            if ($document) {
                $events[] = ['employee_id' => $employee->id, 'employee_name' => $employee->name,
                    'qualification_id' => $item['qualification_id'], 'qualification_name' => $item['name'],
                    'document_id' => $document->id, 'expires_at' => $document->expires_at->toDateString(), 'event' => $event];
            }
        }

        return $events;
    }
}
