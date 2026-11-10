<?php

namespace Tests\Feature;

use App\Models\CredentialDocument;
use App\Models\Facility;
use App\Models\JobRole;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\PhilippineOrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PhilippineOrganizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_includes_all_eighteen_regions_and_is_repeatable_without_reactivating_records(): void
    {
        $this->seed(PhilippineOrganizationSeeder::class);
        $region = Region::where('name', 'Negros Island Region (NIR)')->firstOrFail();
        $facility = $region->facilities()->firstOrFail();
        $region->update(['is_active' => false]);
        $facility->update(['is_active' => false]);
        $this->seed(PhilippineOrganizationSeeder::class);

        $this->assertDatabaseCount('regions', 18);
        $this->assertDatabaseCount('facilities', 18);
        $this->assertDatabaseHas('regions', ['name' => 'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)']);
        $this->assertDatabaseHas('regions', ['name' => 'MIMAROPA Region']);
        $this->assertFalse($region->fresh()->is_active);
        $this->assertFalse($facility->fresh()->is_active);
    }

    public function test_legacy_region_and_hub_are_renamed_without_changing_assignments_or_evidence_references(): void
    {
        $region = Region::factory()->create(['name' => 'Demo Region']);
        $facility = Facility::factory()->create(['region_id' => $region->id, 'name' => 'Demo Logistics Hub']);
        $employee = User::factory()->for(JobRole::factory())->create(['facility_id' => $facility->id]);
        $document = CredentialDocument::factory()->create(['employee_id' => $employee->id]);

        $this->seed(PhilippineOrganizationSeeder::class);

        $this->assertSame('National Capital Region (NCR)', $region->fresh()->name);
        $this->assertSame('Manila Gateway Distribution Hub', $facility->fresh()->name);
        $this->assertSame($region->id, $facility->fresh()->region_id);
        $this->assertSame($facility->id, $employee->fresh()->facility_id);
        $this->assertSame($facility->id, $document->fresh()->facility_id);
        $this->assertDatabaseCount('regions', 18);
        $this->assertDatabaseCount('facilities', 18);
    }

    public function test_duplicate_hub_name_rolls_back_the_region_rename(): void
    {
        $legacy = Region::factory()->create(['name' => 'Demo Region']);
        $hub = Facility::factory()->create(['region_id' => $legacy->id, 'name' => 'Demo Logistics Hub']);
        Facility::factory()->create(['region_id' => $legacy->id, 'name' => 'Manila Gateway Distribution Hub']);
        try {
            $this->seed(PhilippineOrganizationSeeder::class);
            $this->fail('Duplicate hub names must be resolved explicitly.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('NCR hub name already exists', $exception->getMessage());
        }
        $this->assertSame('Demo Region', $legacy->fresh()->name);
        $this->assertSame('Demo Logistics Hub', $hub->fresh()->name);
        $this->assertDatabaseCount('regions', 1);
        $this->assertDatabaseCount('facilities', 2);
    }

    public function test_duplicate_region_name_stops_seeding_without_deleting_or_reassigning_legacy_records(): void
    {
        $legacy = Region::factory()->create(['name' => 'Demo Region']);
        Region::factory()->create(['name' => 'National Capital Region (NCR)']);
        try {
            $this->seed(PhilippineOrganizationSeeder::class);
            $this->fail('Duplicate region names must be resolved explicitly.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('NCR region already exists', $exception->getMessage());
        }
        $this->assertSame('Demo Region', $legacy->fresh()->name);
        $this->assertDatabaseCount('regions', 2);
        $this->assertDatabaseCount('facilities', 0);
    }
}
