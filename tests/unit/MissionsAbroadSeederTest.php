<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\Seeds\MissionsAbroadSeeder;
use Tests\Support\StaffTestCase;

/**
 * The embassies list must match the sponsor's "Mission abroad.docx" and add
 * nothing the document does not say (instruction: never invent mission data).
 */
final class MissionsAbroadSeederTest extends StaffTestCase
{
    public function testLoadsTheDocumentAsUnpublishedDrafts(): void
    {
        $this->seed(MissionsAbroadSeeder::class);

        $missions = $this->db->table('missions')->get()->getResultArray();
        $this->assertCount(21, $missions);
        $this->assertSame(20, $this->db->table('missions')->where('category', 'abroad_embassy')->countAllResults());

        foreach ($missions as $mission) {
            $this->assertSame('draft', $mission['status']);
            $this->assertSame('0', (string) $mission['is_demo']);
            foreach (['address', 'phone', 'email', 'website', 'head_of_mission_name', 'head_of_mission_title'] as $field) {
                $this->assertEmpty($mission[$field], "{$mission['name']}: {$field} is not in the document.");
            }
        }
    }

    public function testRecordsAccreditationsAsNonResidentCoverage(): void
    {
        $this->seed(MissionsAbroadSeeder::class);

        $abuja = $this->db->table('missions')->where('name', 'Embassy in Abuja')->get()->getRowArray();
        $rows  = $this->db->table('mission_coverage')->where('mission_id', $abuja['id'])->get()->getResultArray();

        $this->assertCount(10, $rows);
        $this->assertCount(9, array_filter($rows, static fn (array $row): bool => $row['coverage'] === 'non_resident'));

        $washington = $this->db->table('missions')->where('name', 'Embassy in Washington, D.C.')->get()->getRowArray();
        $canada     = $this->db->table('countries')->where('iso_code', 'CA')->get()->getRowArray();
        $this->assertSame(1, $this->db->table('mission_coverage')->where(['mission_id' => $washington['id'], 'country_id' => $canada['id'], 'coverage' => 'non_resident'])->countAllResults());
    }

    public function testRunningAgainAddsNothingAndKeepsExistingCountries(): void
    {
        $japan = $this->country('JP', 'Japan');

        $this->seed(MissionsAbroadSeeder::class);
        $this->seed(MissionsAbroadSeeder::class);

        $this->assertSame(21, $this->db->table('missions')->countAllResults());
        $this->assertSame(1, $this->db->table('countries')->where('iso_code', 'JP')->countAllResults());
        $this->assertSame($japan, (int) $this->db->table('missions')->where('name', 'Embassy in Tokyo')->get()->getRow()->location_country_id);
    }

    public function testEveryCountryHasAFlag(): void
    {
        foreach (array_keys(MissionsAbroadSeeder::COUNTRIES) as $iso) {
            $this->assertNotNull(flag_url($iso), "No flag for {$iso}");
        }

        $this->assertNull(flag_url('XA'));
        $this->assertNull(flag_url('../x'));
    }
}
