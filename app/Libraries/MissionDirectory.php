<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Read-only queries for the public missions directory and country finder.
 *
 * Only published missions are returned. "Serving a country" means a
 * South Sudanese mission abroad with a coverage record for that country,
 * resident or non-resident (brief section 2).
 */
class MissionDirectory
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byCategory(string $category, string $locale): array
    {
        return $this->published($locale)
            ->where('missions.category', $category)
            ->orderBy('countries.name')
            ->orderBy('missions.name')
            ->get()
            ->getResultArray();
    }

    /**
     * @param list<string> $categories
     *
     * @return array<string, int>
     */
    public function countsByCategory(array $categories, string $locale): array
    {
        $counts = array_fill_keys($categories, 0);
        $rows   = $this->db->table('missions')
            ->select('category, COUNT(*) AS n')
            ->where(['status' => 'published', 'locale' => $locale])
            ->whereIn('category', $categories)
            ->groupBy('category')
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $counts[$row['category']] = (int) $row['n'];
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profile(string $slug, string $locale): ?array
    {
        return $this->published($locale)->where('missions.slug', $slug)->get()->getRowArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function profileById(int $id, string $locale): ?array
    {
        return $this->published($locale)->where('missions.id', $id)->get()->getRowArray();
    }

    /**
     * Countries a mission serves, with resident or non-resident coverage.
     *
     * @return list<array<string, mixed>>
     */
    public function coverageOf(int $missionId): array
    {
        return $this->db->table('mission_coverage')
            ->select('countries.id, countries.iso_code, countries.name, mission_coverage.coverage')
            ->join('countries', 'countries.id = mission_coverage.country_id')
            ->where('mission_coverage.mission_id', $missionId)
            ->orderBy('countries.name')
            ->get()
            ->getResultArray();
    }

    /**
     * Non-resident coverage of several missions at once, for directory cards.
     *
     * @param list<int> $missionIds
     *
     * @return array<int, list<string>> mission id => country names
     */
    public function accreditationsOf(array $missionIds): array
    {
        if ($missionIds === []) {
            return [];
        }

        $rows = $this->db->table('mission_coverage')
            ->select('mission_coverage.mission_id, countries.name')
            ->join('countries', 'countries.id = mission_coverage.country_id')
            ->whereIn('mission_coverage.mission_id', $missionIds)
            ->where('mission_coverage.coverage', 'non_resident')
            ->orderBy('countries.name')
            ->get()
            ->getResultArray();

        $names = [];
        foreach ($rows as $row) {
            $names[(int) $row['mission_id']][] = $row['name'];
        }

        return $names;
    }

    /**
     * Published South Sudanese consulates located in a country.
     *
     * @return list<array<string, mixed>>
     */
    public function consulatesIn(int $countryId, string $locale): array
    {
        return $this->published($locale)
            ->where('missions.category', 'abroad_consulate')
            ->where('missions.location_country_id', $countryId)
            ->orderBy('missions.name')
            ->get()
            ->getResultArray();
    }

    /**
     * South Sudanese missions abroad serving a country, resident first.
     *
     * @return list<array<string, mixed>>
     */
    public function servingCountry(int $countryId, string $locale): array
    {
        return $this->published($locale)
            ->select('mission_coverage.coverage')
            ->join('mission_coverage', 'mission_coverage.mission_id = missions.id')
            ->where('mission_coverage.country_id', $countryId)
            ->like('missions.category', 'abroad_', 'after')
            ->orderBy('mission_coverage.coverage', 'DESC')
            ->orderBy('missions.name')
            ->get()
            ->getResultArray();
    }

    /**
     * The country's own representation in South Sudan, if recorded.
     *
     * @return list<array<string, mixed>>
     */
    public function representationOf(int $countryId, string $locale): array
    {
        return $this->published($locale)
            ->where('missions.represents_country_id', $countryId)
            ->orderBy('missions.name')
            ->get()
            ->getResultArray();
    }

    /**
     * Other published missions abroad serving any country this mission serves.
     *
     * @return list<array<string, mixed>>
     */
    public function relatedTo(int $missionId, string $locale): array
    {
        $countryIds = array_column($this->coverageOf($missionId), 'id');

        if ($countryIds === []) {
            return [];
        }

        return $this->published($locale)
            ->distinct()
            ->join('mission_coverage', 'mission_coverage.mission_id = missions.id')
            ->whereIn('mission_coverage.country_id', $countryIds)
            ->where('missions.id !=', $missionId)
            ->orderBy('missions.name')
            ->get()
            ->getResultArray();
    }

    /**
     * Countries with at least one published mission serving them or
     * represented in South Sudan, for the finder and the country directory.
     *
     * @return list<array<string, mixed>>
     */
    public function countriesWithMissions(string $locale): array
    {
        $served = $this->db->table('mission_coverage')
            ->select('mission_coverage.country_id AS id')
            ->join('missions', 'missions.id = mission_coverage.mission_id')
            ->where(['missions.status' => 'published', 'missions.locale' => $locale])
            ->get()
            ->getResultArray();

        $represented = $this->db->table('missions')
            ->select('represents_country_id AS id')
            ->where(['status' => 'published', 'locale' => $locale])
            ->where('represents_country_id IS NOT NULL')
            ->get()
            ->getResultArray();

        $ids = array_values(array_unique(array_map('intval', array_column([...$served, ...$represented], 'id'))));

        if ($ids === []) {
            return [];
        }

        return $this->db->table('countries')->whereIn('id', $ids)->orderBy('name')->get()->getResultArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function country(string $isoCode): ?array
    {
        return $this->db->table('countries')->where('iso_code', strtoupper($isoCode))->get()->getRowArray();
    }

    private function published(string $locale): \CodeIgniter\Database\BaseBuilder
    {
        return $this->db->table('missions')
            ->select('missions.*, countries.name AS location_country, countries.iso_code AS location_iso, represented.name AS represents_country, represented.iso_code AS represents_iso')
            ->join('countries', 'countries.id = missions.location_country_id', 'left')
            ->join('countries AS represented', 'represented.id = missions.represents_country_id', 'left')
            ->where(['missions.status' => 'published', 'missions.locale' => $locale]);
    }
}
