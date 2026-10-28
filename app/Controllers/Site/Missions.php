<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use CodeIgniter\HTTP\RedirectResponse;
use Config\ContentTypes;

/**
 * MISSIONS: the confirmed hierarchy (Missions Abroad; Foreign Missions in
 * South Sudan), mission profiles, country directories and the country-based
 * Diplomatic Mission Finder (brief section 2).
 */
class Missions extends SiteController
{
    /**
     * @var array<string, list<string>>
     */
    private const GROUPS = [
        'abroad'         => ['abroad_embassy', 'abroad_consulate', 'abroad_permanent_mission', 'abroad_non_resident_mission'],
        'in-south-sudan' => ['foreign_embassy', 'foreign_international_agency'],
    ];

    public function index(): string
    {
        $directory = service('missionDirectory');

        return view('site/missions/index', [
            'groups'    => self::GROUPS,
            'counts'    => $directory->countsByCategory(array_merge(...array_values(self::GROUPS)), $this->locale()),
            'countries' => $directory->countriesWithMissions($this->locale()),
        ]);
    }

    public function group(string $group): string
    {
        $categories = self::GROUPS[$group] ?? $this->notFound();

        return view('site/missions/group', [
            'group'      => $group,
            'categories' => $categories,
            'counts'     => service('missionDirectory')->countsByCategory($categories, $this->locale()),
        ]);
    }

    public function category(string $group, string $segment): string
    {
        $category  = $this->categoryFor($group . '/' . $segment) ?? $this->notFound();
        $directory = service('missionDirectory');
        $missions  = $directory->byCategory($category, $this->locale());

        return view('site/missions/category', [
            'group'          => $group,
            'category'       => $category,
            'missions'       => $missions,
            'accreditations' => $directory->accreditationsOf(array_map('intval', array_column($missions, 'id'))),
        ]);
    }

    public function profile(string $slug): string
    {
        $directory = service('missionDirectory');
        $mission   = $this->requireRow($directory->profile($slug, $this->locale()));
        $id        = (int) $mission['id'];

        return view('site/missions/profile', [
            'mission'    => $mission,
            'bodyHtml'   => service('markdown')->toHtml($mission['body']),
            'coverage'   => $directory->coverageOf($id),
            'related'    => $directory->relatedTo($id, $this->locale()),
            'consulates' => $mission['category'] === 'abroad_embassy' && $mission['location_country_id'] !== null
                ? $directory->consulatesIn((int) $mission['location_country_id'], $this->locale())
                : null,
            'activities' => $this->published('articles')->where(['category' => 'mission_activity', 'mission_id' => $id])->orderBy('published_at', 'DESC')->limit(10)->get()->getResultArray(),
            'media'      => service('media')->forOwner('missions', $id, 'image'),
        ]);
    }

    public function countries(): string
    {
        return view('site/missions/countries', [
            'countries' => service('missionDirectory')->countriesWithMissions($this->locale()),
        ]);
    }

    public function country(string $iso): string
    {
        $directory = service('missionDirectory');
        $country   = $this->requireRow(preg_match('/^[A-Za-z]{2}$/', $iso) === 1 ? $directory->country($iso) : null);

        return view('site/missions/country', [
            'country'        => $country,
            'serving'        => $directory->servingCountry((int) $country['id'], $this->locale()),
            'representation' => $directory->representationOf((int) $country['id'], $this->locale()),
        ]);
    }

    /**
     * Mission Finder form target: send the visitor to the country page.
     */
    public function finder(): RedirectResponse
    {
        $iso = $this->request->getGet('country');

        if (! is_string($iso) || preg_match('/^[A-Za-z]{2}$/', $iso) !== 1) {
            return redirect()->to(locale_url('missions/countries'));
        }

        return redirect()->to(locale_url('missions/countries/' . strtoupper($iso)));
    }

    private function categoryFor(string $path): ?string
    {
        foreach (config(ContentTypes::class)->missionCategories as $key => $category) {
            if ($category['path'] === $path) {
                return $key;
            }
        }

        return null;
    }
}
