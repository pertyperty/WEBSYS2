<?php

namespace App\Actions;

use App\Enums\CourseStatus;
use App\Enums\CredentialEventType;
use App\Enums\CredentialStatus;
use App\Enums\TrainingStatus;
use App\Enums\UserRole;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\SafetyDomain;
use App\Models\TrainingCourse;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CreateSyntheticDataset
{
    public const array TABLES = ['users', 'safety_domains', 'job_roles', 'qualifications', 'job_role_qualification', 'training_courses', 'employee_trainings', 'credential_documents', 'credential_events', 'employee_qualification', 'compliance_assessments', 'audits', 'audit_findings', 'finding_updates', 'notifications'];

    public function __construct(
        private SelectEffectiveCredential $selection,
        private RecordComplianceAssessment $history,
        private ReconcileExpiryAlerts $alerts,
        private CaptureAudit $capture,
    ) {}

    /** @return array<string, int> Counts added by this run, excluding pre-existing organization data. */
    public function handle(string $password): array
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Synthetic data is available only in local or testing environments.');
        }
        $path = 'synthetic/'.Str::uuid().'/evidence.png';
        try {
            return DB::transaction(function () use ($password, $path): array {
                if (User::where('email', 'like', '%@checkpoint.example')->exists()
                    || JobRole::whereIn('name', ['Healthcare Logistics Associate', 'Operations Observer'])->exists()
                    || SafetyDomain::where('name', 'Healthcare Logistics Safety')->exists()) {
                    throw new RuntimeException('Synthetic addresses or catalog names already exist. No records were changed; use the existing dataset or a separate empty database.');
                }
                $facilities = Facility::active()->whereHas('region', fn ($query) => $query->active())
                    ->orderBy('region_id')->orderBy('id')->get()->unique('region_id')->values();
                if ($facilities->count() !== 18) {
                    throw new RuntimeException('Provide one active facility in each of the 18 regions first (php artisan db:seed on a fresh installation).');
                }
                $before = $this->counts();
                if (! Storage::disk('local')->put($path, file_get_contents(database_path('fixtures/synthetic-evidence.png')))) {
                    throw new RuntimeException('The harmless evidence fixture could not be stored. No records were changed.');
                }
                $hash = Hash::make($password);
                $today = CarbonImmutable::now('Asia/Manila')->startOfDay();
                $officer = $this->account('officer', 'Morgan Reyes', UserRole::ComplianceOfficer, $hash);
                $this->account('admin', 'Alex Rivera', UserRole::Admin, $hash);
                $unassigned = $this->account('unassigned', 'Jamie Flores', UserRole::Staff, $hash);
                $inactive = $this->account('inactive', 'Robin Garcia', UserRole::Staff, $hash, ['is_active' => false]);
                $domain = SafetyDomain::create(['name' => 'Healthcare Logistics Safety']);
                $job = JobRole::create(['name' => 'Healthcare Logistics Associate']);
                $observer = JobRole::create(['name' => 'Operations Observer']);
                $coldChain = Qualification::create(['name' => 'Cold Chain Handling', 'safety_domain_id' => $domain->id, 'requires_training' => true, 'requires_expiry' => true, 'is_active' => true]);
                $orientation = Qualification::create(['name' => 'Workplace Safety Orientation', 'safety_domain_id' => $domain->id, 'requires_training' => false, 'requires_expiry' => false, 'is_active' => true]);
                $job->qualifications()->attach([$coldChain->id, $orientation->id]);
                $course = TrainingCourse::create(['title' => 'Cold Chain Handling Essentials', 'qualification_id' => $coldChain->id, 'status' => CourseStatus::Published]);
                TrainingCourse::create(['title' => 'Cold Chain Refresher', 'qualification_id' => $coldChain->id, 'status' => CourseStatus::Draft]);
                TrainingCourse::create(['title' => 'Cold Chain Handling Previous Offering', 'qualification_id' => $coldChain->id, 'status' => CourseStatus::Archived]);

                foreach ($facilities as $index => $facility) {
                    $number = sprintf('%02d', $index + 1);
                    $manager = $this->account('manager.'.$number, 'Taylor Mendoza '.$number, UserRole::FacilityManager, $hash, ['facility_id' => $facility->id]);
                    $this->account('director.'.$number, 'Jordan Cruz '.$number, UserRole::RegionalDirector, $hash, ['region_id' => $facility->region_id]);
                    $this->account('auditor.'.$number, 'Casey Ramos '.$number, UserRole::Auditor, $hash, ['region_id' => $facility->region_id]);
                    foreach (['current' => 'Avery Santos', 'expiring' => 'Drew Bautista', 'expired' => 'Reese Castillo', 'pending' => 'Riley Torres', 'rejected' => 'Quinn Aquino', 'missing' => 'Blair Villanueva', 'incomplete' => 'Skyler Lim', 'renewal' => 'Cameron Dizon'] as $scenario => $name) {
                        $employee = $this->account('staff.'.$number.'.'.$scenario, $name.' '.$number, UserRole::Staff, $hash, ['facility_id' => $facility->id, 'job_role_id' => $job->id]);
                        $this->evidenceScenario($employee, $officer, $course, $coldChain, $orientation, $scenario, $path, $today);
                        $this->history->handle($employee, 'synthetic_setup', $officer, $today);
                        $this->alerts->handle($employee, $today);
                    }
                    $employee = $this->account('staff.'.$number.'.observer', 'Sam Navarro '.$number, UserRole::Staff, $hash, ['facility_id' => $facility->id, 'job_role_id' => $observer->id]);
                    $this->history->handle($employee, 'synthetic_setup', $officer, $today);
                    $audit = $this->capture->handle($officer, ['title' => 'Facility safety review - '.$facility->name, 'region_id' => $facility->region_id, 'facility_id' => $facility->id]);
                    $this->findings($audit, $manager, $officer, $today);
                }
                $this->history->handle($unassigned, 'synthetic_setup', $officer, $today);
                $this->history->handle($inactive, 'synthetic_setup', $officer, $today);

                return collect($this->counts())->map(fn (int $count, string $table): int => $count - $before[$table])->all();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    /** @param array{is_active?: bool, facility_id?: int, job_role_id?: int, region_id?: int} $assignment */
    private function account(string $address, string $name, UserRole $role, string $hash, array $assignment = []): User
    {
        return User::factory()->create(['email' => $address.'@checkpoint.example', 'name' => $name, 'password' => $hash, 'role' => $role, ...$assignment]);
    }

    private function evidenceScenario(User $employee, User $officer, TrainingCourse $course, Qualification $coldChain, Qualification $orientation, string $scenario, string $path, CarbonImmutable $today): void
    {
        $training = EmployeeTraining::create(['employee_id' => $employee->id, 'training_course_id' => $course->id, 'enrolled_at' => $today->subDays(180)]);
        if ($scenario !== 'incomplete') {
            $training->forceFill(['status' => TrainingStatus::Completed, 'completed_at' => $today->subDays(120), 'completed_by' => $officer->id, 'feedback' => 'Practical handling checks completed.'])->save();
        }
        if ($scenario === 'missing') {
            return;
        }
        foreach ([$coldChain, $orientation] as $qualification) {
            $status = match ($scenario) {
                'pending' => CredentialStatus::Pending,
                'rejected' => CredentialStatus::Rejected,
                'incomplete' => $qualification->requires_training ? CredentialStatus::Pending : CredentialStatus::Approved,
                default => CredentialStatus::Approved,
            };
            $expiry = $qualification->requires_expiry ? match ($scenario) {
                'expired' => $today->subDay(),
                'expiring' => $today->addDays(15),
                default => $today->addYear(),
            } : null;
            $this->document($employee, $officer, $qualification, $qualification->requires_training ? $training : null, $status, $path, $today->subDays(90), $expiry);
            if ($scenario === 'renewal' && $qualification->requires_training) {
                $this->document($employee, $officer, $qualification, $training, CredentialStatus::Pending, $path, $today, $today->addYears(2));
            }
            $effective = $this->selection->find($employee, $qualification);
            if ($effective) {
                $employee->qualifications()->attach($qualification->id, ['credential_document_id' => $effective->id]);
            }
        }
    }

    private function document(User $employee, User $officer, Qualification $qualification, ?EmployeeTraining $training, CredentialStatus $status, string $path, CarbonImmutable $issuedOn, ?CarbonImmutable $expiresOn): void
    {
        $document = CredentialDocument::factory()->create([
            'employee_id' => $employee->id, 'facility_id' => $employee->facility_id, 'job_role_id' => $employee->job_role_id,
            'qualification_id' => $qualification->id, 'employee_training_id' => $training?->id,
            'status' => $status, 'private_path' => $path, 'mime_type' => 'image/png', 'file_size' => Storage::disk('local')->size($path),
            'issued_at' => $issuedOn->toDateString(), 'expires_at' => $expiresOn?->toDateString(),
        ]);
        $document->events()->create(['actor_id' => $employee->id, 'type' => CredentialEventType::Submitted]);
        if ($status !== CredentialStatus::Pending) {
            $document->events()->create(['actor_id' => $officer->id, 'type' => CredentialEventType::from($status->value), 'note' => $status === CredentialStatus::Rejected ? 'Replace the unreadable evidence with a clear copy.' : null]);
        }
    }

    private function findings(Audit $audit, User $manager, User $officer, CarbonImmutable $today): void
    {
        foreach (['open', 'in_progress', 'pending_verification', 'resolved'] as $status) {
            $finding = AuditFinding::create(['audit_id' => $audit->id, 'facility_id' => $audit->facility_id, 'region_id' => $audit->region_id, 'title' => 'Review handling records - '.str_replace('_', ' ', $status), 'description' => 'Check handling records and submit corrective evidence for officer verification.', 'assignee_id' => $manager->id, 'due_on' => $today->addDays(14)->toDateString()]);
            if ($status === 'open') {
                continue;
            }
            $finding->updates()->create(['author_id' => $manager->id, 'status' => 'in_progress', 'note' => 'Handling records are being checked.']);
            if (in_array($status, ['pending_verification', 'resolved'], true)) {
                $finding->updates()->create(['author_id' => $manager->id, 'status' => 'pending_verification', 'note' => 'Records checked; submitted for verification.']);
            }
            if ($status === 'resolved') {
                $finding->updates()->create(['author_id' => $officer->id, 'status' => 'resolved', 'note' => 'Corrective records verified.']);
            }
            $finding->forceFill(['status' => $status, 'resolved_at' => $status === 'resolved' ? now() : null])->save();
        }
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return collect(self::TABLES)->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])->all();
    }
}
