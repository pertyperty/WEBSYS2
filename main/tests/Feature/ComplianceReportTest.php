<?php

namespace Tests\Feature;

use App\Actions\BuildComplianceReport;
use App\Enums\CredentialStatus;
use App\Enums\UserRole;
use App\Models\AuditFinding;
use App\Models\CredentialDocument;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ComplianceReportTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['readiness'])]
    #[TestWith(['expiry'])]
    #[TestWith(['training'])]
    public function test_employee_reports_and_previews_respect_facility_scope(string $type): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 12:00', 'Asia/Manila'));
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['name' => 'Visible employee']);
        $outside = User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['name' => 'Outside employee']);
        $qualification = Qualification::factory()->create();
        foreach ([$employee, $outside] as $person) {
            $person->jobRole->qualifications()->attach($qualification);
            CredentialDocument::factory()->create(['employee_id' => $person->id, 'qualification_id' => $qualification->id, 'status' => CredentialStatus::Approved, 'expires_at' => '2026-11-20']);
            EmployeeTraining::factory()->create(['employee_id' => $person->id]);
        }
        $manager = User::factory()->create(['role' => UserRole::FacilityManager, 'facility_id' => $employee->facility_id]);
        $this->actingAs($manager)->get('/reports?type='.$type)->assertOk()->assertSee($employee->name)->assertDontSee($outside->name);

        $response = $this->get('/reports/download?type='.$type);

        $response->assertOk()->assertDownload('checkpoint-'.$type.'-2026-11-11.csv');
        $csv = $response->streamedContent();
        $this->assertStringContainsString($employee->name, $csv);
        $this->assertStringNotContainsString($outside->name, $csv);
        $this->assertStringNotContainsString('credentials/', $csv);
        $this->get('/reports/download?type='.$type.'&facility_id='.$outside->facility_id)->assertSessionHasErrors('facility_id');
        $this->get('/reports?type='.$type.'&q=Outside')->assertOk()->assertDontSee($outside->name)->assertSee('No matching results');
    }

    public function test_findings_report_is_scoped_and_excludes_corrective_notes(): void
    {
        $finding = AuditFinding::factory()->create(['title' => 'Visible finding']);
        $outside = AuditFinding::factory()->create(['title' => 'Outside finding']);
        $finding->updates()->create(['author_id' => $finding->assignee_id, 'status' => 'in_progress', 'note' => 'PRIVATE CORRECTIVE NOTE']);
        $this->actingAs($finding->assignee)->get('/reports?type=findings')->assertOk()->assertSee($finding->title)->assertDontSee($outside->title);
        $csv = $this->get('/reports/download?type=findings')->assertOk()->streamedContent();
        $this->assertStringContainsString($finding->title, $csv);
        $this->assertStringNotContainsString($outside->title, $csv);
        $this->assertStringNotContainsString('PRIVATE CORRECTIVE NOTE', $csv);
    }

    public function test_director_and_unassigned_scopes_do_not_expand_reports(): void
    {
        $employee = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $outside = User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
        $director = User::factory()->create(['role' => UserRole::RegionalDirector, 'region_id' => $employee->facility->region_id]);
        $csv = $this->actingAs($director)->get('/reports/download')->assertOk()->streamedContent();
        $this->assertStringContainsString($employee->name, $csv);
        $this->assertStringNotContainsString($outside->name, $csv);
        $this->get('/reports?facility_id='.$outside->facility_id)->assertSessionHasErrors('facility_id');
        $director->forceFill(['region_id' => null])->save();
        $this->get('/reports')->assertOk()->assertDontSee($employee->name)->assertSee('No matching results');
    }

    public function test_csv_neutralizes_formula_prefixes_including_leading_whitespace(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->for(Facility::factory())->for(JobRole::factory())->create(['name' => '=HYPERLINK("https://example.com")']);
        $csv = $this->actingAs($admin)->get('/reports/download')->assertOk()->streamedContent();
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $csv);
        rewind($stream);
        fgetcsv($stream, escape: '');
        $row = fgetcsv($stream, escape: '');
        fclose($stream);
        $this->assertSame('\'=HYPERLINK("https://example.com")', $row[0]);
        $safe = app(BuildComplianceReport::class)->safeCsvRow([' +1', '-2', '@SUM(A1)', "\t=1", "\r1", 'Normal', null]);
        $this->assertSame(["' +1", "'-2", "'@SUM(A1)", "'\t=1", "'\r1", 'Normal', ''], $safe);
    }

    public function test_preview_limits_rows_but_download_includes_every_matching_employee(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $facility = Facility::factory()->create();
        User::factory()->count(26)->create(['facility_id' => $facility->id]);
        $response = $this->actingAs($admin)->get('/reports');
        $response->assertOk()->assertViewHas('rows', fn (array $rows): bool => count($rows) === 25);
        $csv = $this->get('/reports/download')->assertOk()->streamedContent();
        $this->assertCount(27, array_filter(explode("\n", trim($csv))));
    }

    public function test_reports_require_authentication_and_a_permitted_role_and_validate_inputs(): void
    {
        $this->get('/reports')->assertRedirect('/login');
        $this->actingAs(User::factory()->for(Facility::factory())->for(JobRole::factory())->create())->get('/reports/download')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->get('/reports?type=invalid')->assertSessionHasErrors('type');
        $this->get('/reports?facility_id=999999')->assertSessionHasErrors('facility_id');
        $this->get('/reports?q='.str_repeat('x', 101))->assertSessionHasErrors('q');
    }
}
