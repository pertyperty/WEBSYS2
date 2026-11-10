<?php

namespace Tests\Feature;

use App\Enums\CredentialEventType;
use App\Enums\CredentialStatus;
use App\Models\CredentialDocument;
use App\Models\CredentialEvent;
use App\Models\EmployeeTraining;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Qualification;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CredentialSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryUploads = [];

    public function test_staff_submission_stores_private_evidence_and_ignores_privileged_fields(): void
    {
        Storage::fake('local');
        $staff = $this->staff();
        $qualification = Qualification::factory()->create();
        $this->actingAs($staff)->get('/credentials/create')->assertOk()->assertSee($qualification->name);
        $this->post('/credentials', array_merge($this->payload($qualification), [
            'employee_id' => 999999, 'status' => 'approved', 'private_path' => 'public/evil.pdf', 'reference' => 'chosen-reference',
        ]))->assertSessionHasNoErrors();
        $document = CredentialDocument::firstOrFail();
        $this->assertSame($staff->id, $document->employee_id);
        $this->assertSame($staff->facility_id, $document->facility_id);
        $this->assertSame($staff->job_role_id, $document->job_role_id);
        $this->assertSame(CredentialStatus::Pending, $document->status);
        $this->assertTrue(Str::isUuid($document->reference));
        $this->assertSame('application/pdf', $document->mime_type);
        Storage::disk('local')->assertExists($document->private_path);
        $this->assertStringStartsWith('credentials/', $document->private_path);
        $this->assertStringNotContainsString('client filename', $document->private_path);
        $this->assertArrayNotHasKey('private_path', $document->toArray());
        $this->assertSame(CredentialEventType::Submitted, $document->events->first()->type);
        $this->assertSame($staff->id, $document->events->first()->actor_id);
        $this->assertDatabaseCount('employee_qualification', 0);
        $this->get('/credentials')->assertOk()->assertSee($document->reference)->assertDontSee($document->private_path);
    }

    public static function imageFiles(): array
    {
        return [
            'png' => ['proof.png', 'image/png', 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j2ioAAAAASUVORK5CYII='],
            'jpeg' => ['proof.jpg', 'image/jpeg', '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EH//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EH//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EH//2Q=='],
        ];
    }

    #[DataProvider('imageFiles')]
    public function test_valid_image_content_is_accepted(string $name, string $mime, string $bytes): void
    {
        Storage::fake('local');
        $payload = $this->payload(Qualification::factory()->create());
        $payload['file'] = $this->contentUpload($name, base64_decode($bytes));
        $this->actingAs($this->staff())->post('/credentials', $payload)->assertSessionHasNoErrors();
        $this->assertSame($mime, CredentialDocument::firstOrFail()->mime_type);
    }

    public function test_renamed_scripts_malformed_images_and_oversized_files_are_rejected(): void
    {
        Storage::fake('local');
        $payload = $this->payload(Qualification::factory()->create());
        $this->actingAs($this->staff());
        $payload['file'] = $this->contentUpload('evil.pdf', '<?php echo "unsafe";');
        $this->post('/credentials', $payload)->assertSessionHasErrors('file');
        $payload['file'] = $this->contentUpload('broken.png', "\x89PNG\r\n\x1a\n");
        $this->post('/credentials', $payload)->assertSessionHasErrors('file');
        config(['credentials.max_upload_kib' => 1]);
        $payload['file'] = UploadedFile::fake()->create('large.pdf', 2, 'application/pdf');
        $this->post('/credentials', $payload)->assertSessionHasErrors('file');
        $this->assertDatabaseCount('credential_documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_dates_required_fields_and_non_expiring_qualifications_are_validated(): void
    {
        Storage::fake('local');
        $this->freezeTime();
        $qualification = Qualification::factory()->create();
        $this->actingAs($this->staff())->post('/credentials', [])->assertSessionHasErrors(['qualification_id', 'issued_at', 'file']);
        $payload = $this->payload($qualification);
        $payload['issued_at'] = now('Asia/Manila')->addDay()->toDateString();
        $this->post('/credentials', $payload)->assertSessionHasErrors('issued_at');
        $payload = $this->payload($qualification);
        $payload['expires_at'] = now('Asia/Manila')->subDay()->toDateString();
        $this->post('/credentials', $payload)->assertSessionHasErrors('expires_at');
        unset($payload['expires_at']);
        $this->post('/credentials', $payload)->assertSessionHasErrors('expires_at');
        $qualification->update(['requires_expiry' => false]);
        $this->post('/credentials', $payload)->assertSessionHasNoErrors();
        $this->assertNull(CredentialDocument::firstOrFail()->expires_at);
    }

    public function test_inactive_qualifications_and_domains_cannot_receive_new_evidence(): void
    {
        Storage::fake('local');
        $qualification = Qualification::factory()->create(['is_active' => false]);
        $this->actingAs($this->staff())->post('/credentials', $this->payload($qualification))->assertSessionHasErrors('qualification_id');
        $qualification->update(['is_active' => true]);
        $qualification->safetyDomain->update(['is_active' => false]);
        $this->post('/credentials', $this->payload($qualification))->assertSessionHasErrors('qualification_id');
        $this->assertDatabaseCount('credential_documents', 0);
    }

    public function test_training_evidence_requires_the_submitters_matching_enrollment_but_may_await_completion(): void
    {
        Storage::fake('local');
        $enrollment = EmployeeTraining::factory()->create();
        $qualification = $enrollment->trainingCourse->qualification;
        $staff = $enrollment->employee;
        $this->actingAs($staff);
        $this->post('/credentials', $this->payload($qualification))->assertSessionHasErrors('employee_training_id');
        $other = EmployeeTraining::factory()->create();
        $payload = $this->payload($qualification);
        $payload['employee_training_id'] = $other->id;
        $this->post('/credentials', $payload)->assertSessionHasErrors('employee_training_id');
        $wrongCourse = EmployeeTraining::factory()->for($staff, 'employee')->create();
        $payload['employee_training_id'] = $wrongCourse->id;
        $this->post('/credentials', $payload)->assertSessionHasErrors('employee_training_id');
        $payload['employee_training_id'] = $enrollment->id;
        $this->post('/credentials', $payload)->assertSessionHasNoErrors();
        $this->assertSame($enrollment->id, CredentialDocument::firstOrFail()->employee_training_id);
        $payload['qualification_id'] = Qualification::factory()->create()->id;
        $this->post('/credentials', $payload)->assertSessionHasErrors('employee_training_id');
    }

    public function test_resubmission_creates_a_new_file_record_and_submission_event(): void
    {
        Storage::fake('local');
        $qualification = Qualification::factory()->create();
        $this->actingAs($this->staff());
        $this->post('/credentials', $this->payload($qualification))->assertSessionHasNoErrors();
        $first = CredentialDocument::firstOrFail();
        $this->post('/credentials', $this->payload($qualification))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('credential_documents', 2);
        $this->assertDatabaseCount('credential_events', 2);
        $this->assertCount(2, Storage::disk('local')->allFiles());
        $this->assertSame(CredentialStatus::Pending, $first->fresh()->status);
        Storage::disk('local')->assertExists($first->private_path);
    }

    public function test_failed_history_insert_rolls_back_metadata_and_cleans_up_the_private_file(): void
    {
        Storage::fake('local');
        $qualification = Qualification::factory()->create();
        $staff = $this->staff();
        $older = CredentialDocument::factory()->for($staff, 'employee')->for($qualification)->create(['status' => CredentialStatus::Approved]);
        Storage::disk('local')->put($older->private_path, '%PDF-1.4 older evidence');
        $eventName = 'eloquent.creating: '.CredentialEvent::class;
        Event::listen($eventName, function (): void {
            throw new RuntimeException('Simulated persistence failure');
        });
        try {
            $this->actingAs($staff)->post('/credentials', $this->payload($qualification))->assertSessionHasErrors(['file' => 'The submission could not be saved. Please try again.']);
            $this->assertDatabaseCount('credential_documents', 1);
            $this->assertSame(CredentialStatus::Approved, $older->fresh()->status);
            $this->assertDatabaseCount('credential_events', 0);
            $this->assertSame([$older->private_path], Storage::disk('local')->allFiles());
        } finally {
            Event::forget($eventName);
        }
    }

    public function test_failed_private_write_does_not_create_a_submission(): void
    {
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::set('local', $disk);
        $this->actingAs($this->staff())->post('/credentials', $this->payload(Qualification::factory()->create()))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('credential_documents', 0);
        $this->assertDatabaseCount('credential_events', 0);
    }

    private function staff(): User
    {
        return User::factory()->for(Facility::factory())->for(JobRole::factory())->create();
    }

    private function payload(Qualification $qualification): array
    {
        return [
            'qualification_id' => $qualification->id,
            'issued_at' => now('Asia/Manila')->toDateString(),
            'expires_at' => now('Asia/Manila')->addYear()->toDateString(),
            'file' => $this->contentUpload('client filename.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n"),
        ];
    }

    private function contentUpload(string $name, string $bytes): UploadedFile
    {
        $temporary = UploadedFile::fake()->createWithContent($name, $bytes);
        $this->temporaryUploads[] = $temporary;

        return new UploadedFile($temporary->getRealPath(), $name, 'application/pdf', UPLOAD_ERR_OK, true);
    }
}
