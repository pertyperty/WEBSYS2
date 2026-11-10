<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PhilippineOrganizationSeeder extends Seeder
{
    /**
     * Philippine regions follow the PSA PSGC; hub names are fictional operations locations.
     *
     * @var array<string, string>
     */
    private const array HUBS = [
        'National Capital Region (NCR)' => 'Manila Gateway Distribution Hub',
        'Cordillera Administrative Region (CAR)' => 'Baguio Highland Distribution Hub',
        'Region I (Ilocos Region)' => 'Laoag North Coast Logistics Hub',
        'Region II (Cagayan Valley)' => 'Tuguegarao Valley Distribution Hub',
        'Region III (Central Luzon)' => 'Clark Gateway Logistics Hub',
        'Region IV-A (CALABARZON)' => 'Calamba South Luzon Distribution Hub',
        'MIMAROPA Region' => 'Puerto Princesa Island Logistics Hub',
        'Region V (Bicol Region)' => 'Legazpi Bicol Distribution Hub',
        'Region VI (Western Visayas)' => 'Iloilo Panay Logistics Hub',
        'Region VII (Central Visayas)' => 'Mandaue Central Visayas Distribution Hub',
        'Region VIII (Eastern Visayas)' => 'Tacloban Eastern Gateway Hub',
        'Region IX (Zamboanga Peninsula)' => 'Zamboanga Peninsula Logistics Hub',
        'Region X (Northern Mindanao)' => 'Cagayan de Oro Northern Gateway Hub',
        'Region XI (Davao Region)' => 'Davao Southern Gateway Distribution Hub',
        'Region XII (SOCCSKSARGEN)' => 'General Santos South Coast Logistics Hub',
        'Region XIII (Caraga)' => 'Butuan Caraga Distribution Hub',
        'Bangsamoro Autonomous Region in Muslim Mindanao (BARMM)' => 'Cotabato Bangsamoro Logistics Hub',
        'Negros Island Region (NIR)' => 'Bacolod Negros Gateway Hub',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->renameLegacyOrganization();
            foreach (self::HUBS as $name => $hub) {
                $region = Region::firstOrCreate(['name' => $name]);
                $region->facilities()->firstOrCreate(['name' => $hub]);
            }
        });
    }

    private function renameLegacyOrganization(): void
    {
        $legacy = Region::where('name', 'Demo Region')->lockForUpdate()->first();
        if (! $legacy) {
            return;
        }
        $regionName = 'National Capital Region (NCR)';
        if (Region::where('name', $regionName)->whereKeyNot($legacy->id)->exists()) {
            throw new RuntimeException('The NCR region already exists. Resolve the legacy region name in Administration before seeding; existing assignments were not changed.');
        }
        $hub = $legacy->facilities()->where('name', 'Demo Logistics Hub')->lockForUpdate()->first();
        $hubName = self::HUBS[$regionName];
        if ($hub && $legacy->facilities()->where('name', $hubName)->whereKeyNot($hub->id)->exists()) {
            throw new RuntimeException('The NCR hub name already exists. Resolve the duplicate facility name before seeding; existing assignments were not changed.');
        }
        $legacy->update(['name' => $regionName]);
        $hub?->update(['name' => $hubName]);
    }
}
