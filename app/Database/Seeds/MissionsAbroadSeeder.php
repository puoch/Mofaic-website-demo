<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Loads the South Sudanese embassies abroad listed in "Mission abroad.docx",
 * supplied by the project sponsor on 28 October 2026 (project files:
 * missions/Mission abroad.docx). The document ends mid-word on the United
 * States line; the sponsor confirmed that embassy covers Canada only.
 *
 * Only what the document says is loaded: host country, city, and the
 * countries each embassy is also accredited to. Addresses, telephones,
 * emails and heads of mission are not in the document and stay empty
 * ("To be confirmed" on the public page) until the Ministry supplies them.
 *
 * Missions are saved as drafts for staff to check and publish. Safe to run
 * again: countries, missions and coverage that already exist are left alone.
 *
 *   php spark db:seed MissionsAbroadSeeder
 *   or "Load embassies" on /maintenance
 */
class MissionsAbroadSeeder extends Seeder
{
    /**
     * ISO 3166 code => name as written in the document. EU is the ISO
     * exceptionally reserved code for the European Union.
     *
     * @var array<string, string>
     */
    public const COUNTRIES = [
        'AE' => 'United Arab Emirates', 'AT' => 'Austria', 'AU' => 'Australia', 'AZ' => 'Azerbaijan',
        'BE' => 'Belgium', 'BI' => 'Burundi', 'BJ' => 'Benin', 'CA' => 'Canada', 'CI' => 'Ivory Coast',
        'CM' => 'Cameroon', 'CN' => 'China', 'CZ' => 'Czechia', 'DE' => 'Germany', 'DK' => 'Denmark',
        'EG' => 'Egypt', 'ES' => 'Spain', 'EU' => 'European Union', 'FI' => 'Finland', 'FR' => 'France',
        'GB' => 'United Kingdom', 'GM' => 'Gambia', 'GN' => 'Guinea', 'GQ' => 'Equatorial Guinea',
        'GR' => 'Greece', 'IL' => 'Israel', 'IN' => 'India', 'IS' => 'Iceland', 'IT' => 'Italy',
        'JP' => 'Japan', 'KE' => 'Kenya', 'KR' => 'South Korea', 'LK' => 'Sri Lanka', 'LU' => 'Luxembourg',
        'ML' => 'Mali', 'MY' => 'Malaysia', 'NA' => 'Namibia', 'NE' => 'Niger', 'NG' => 'Nigeria',
        'NO' => 'Norway', 'PT' => 'Portugal', 'RU' => 'Russia', 'RW' => 'Rwanda', 'SC' => 'Seychelles',
        'SD' => 'Sudan', 'SE' => 'Sweden', 'SN' => 'Senegal', 'TR' => 'Turkey', 'UG' => 'Uganda',
        'US' => 'United States', 'ZA' => 'South Africa',
    ];

    /**
     * [name, category, host country, city, also accredited to]
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: list<string>}>
     */
    public const MISSIONS = [
        ['Embassy in Brussels', 'abroad_embassy', 'BE', 'Brussels', ['LU', 'EU']],
        ['Embassy in Beijing', 'abroad_embassy', 'CN', 'Beijing', ['AU', 'MY', 'KR']],
        ['Embassy in Cairo', 'abroad_embassy', 'EG', 'Cairo', []],
        ['Embassy in Paris', 'abroad_embassy', 'FR', 'Paris', ['PT', 'ES']],
        ['Embassy in Berlin', 'abroad_embassy', 'DE', 'Berlin', ['AT', 'CZ']],
        ['Embassy in New Delhi', 'abroad_embassy', 'IN', 'New Delhi', ['LK']],
        ['Embassy in Tel Aviv', 'abroad_embassy', 'IL', 'Tel Aviv', []],
        ['Embassy in Rome', 'abroad_embassy', 'IT', 'Rome', ['GR']],
        ['Embassy in Tokyo', 'abroad_embassy', 'JP', 'Tokyo', []],
        ['Embassy in Nairobi', 'abroad_embassy', 'KE', 'Nairobi', []],
        ['Embassy in Abuja', 'abroad_embassy', 'NG', 'Abuja', ['BJ', 'CM', 'GQ', 'GM', 'GN', 'CI', 'ML', 'NE', 'SN']],
        ['Embassy in Oslo', 'abroad_embassy', 'NO', 'Oslo', ['DK', 'FI', 'IS', 'SE']],
        ['Embassy in Moscow', 'abroad_embassy', 'RU', 'Moscow', ['AZ']],
        ['Embassy in Pretoria', 'abroad_embassy', 'ZA', 'Pretoria', ['NA', 'SC']],
        ['Embassy in Khartoum', 'abroad_embassy', 'SD', 'Khartoum', []],
        ['Embassy in Ankara', 'abroad_embassy', 'TR', 'Ankara', []],
        ['Embassy in Kampala', 'abroad_embassy', 'UG', 'Kampala', ['BI', 'RW']],
        ['Embassy in Abu Dhabi', 'abroad_embassy', 'AE', 'Abu Dhabi', []],
        ['Consulate-General in Dubai', 'abroad_consulate', 'AE', 'Dubai', []],
        ['Embassy in London', 'abroad_embassy', 'GB', 'London', []],
        ['Embassy in Washington, D.C.', 'abroad_embassy', 'US', 'Washington, D.C.', ['CA']],
    ];

    public function run(): void
    {
        $content   = service('content');
        $countries = $this->countries();

        foreach (self::MISSIONS as [$name, $category, $host, $city, $covers]) {
            $existing = $this->db->table('missions')
                ->where(['name' => $name, 'category' => $category, 'location_country_id' => $countries[$host]])
                ->get()
                ->getRowArray();

            $id = $existing === null
                ? $content->save('missions', null, [
                    'name'                => $name,
                    'category'            => $category,
                    'location_country_id' => $countries[$host],
                    'city'                => $city,
                ], null, 'Loaded from Mission abroad.docx')
                : (int) $existing['id'];

            $this->cover($id, $countries[$host], 'resident');
            foreach ($covers as $iso) {
                $this->cover($id, $countries[$iso], 'non_resident');
            }
        }
    }

    /**
     * @return array<string, int> ISO code => country id
     */
    private function countries(): array
    {
        $now = date('Y-m-d H:i:s');
        $ids = [];

        foreach (self::COUNTRIES as $iso => $name) {
            $row = $this->db->table('countries')->where('iso_code', $iso)->get()->getRowArray();

            if ($row === null) {
                $this->db->table('countries')->insert(['iso_code' => $iso, 'name' => $name, 'is_demo' => 0, 'created_at' => $now, 'updated_at' => $now]);
                $ids[$iso] = (int) $this->db->insertID();
            } else {
                $ids[$iso] = (int) $row['id'];
            }
        }

        return $ids;
    }

    private function cover(int $missionId, int $countryId, string $coverage): void
    {
        $where = ['mission_id' => $missionId, 'country_id' => $countryId];

        if ($this->db->table('mission_coverage')->where($where)->countAllResults() === 0) {
            $this->db->table('mission_coverage')->insert($where + ['coverage' => $coverage]);
        }
    }
}
