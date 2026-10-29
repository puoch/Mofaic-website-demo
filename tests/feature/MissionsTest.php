<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\StaffTestCase;

/**
 * Missions directories, profiles, related missions, non-resident coverage and
 * the country finder (MIS-01 to MIS-06, HOME-06).
 *
 * All countries and missions here are test fixtures using ISO user-assigned
 * codes; none is real.
 */
final class MissionsTest extends StaffTestCase
{
    private int $alpha;
    private int $beta;
    private int $gamma;
    private int $embassy;
    private int $nonResident;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alpha = $this->country('XA', 'Testland Alpha');
        $this->beta  = $this->country('XB', 'Testland Beta');
        $this->gamma = $this->country('XC', 'Testland Gamma');

        $this->embassy     = $this->content('missions', ['name' => 'Test Embassy Alpha', 'category' => 'abroad_embassy', 'location_country_id' => $this->alpha, 'city' => 'Alpha City', 'phone' => '+000 1', 'email' => 'a@example.invalid', 'head_of_mission_name' => 'Test Head']);
        $this->nonResident = $this->content('missions', ['name' => 'Test Non-resident Mission', 'category' => 'abroad_non_resident_mission', 'location_country_id' => $this->beta]);
        $this->content('missions', ['name' => 'Test Foreign Embassy', 'category' => 'foreign_embassy', 'represents_country_id' => $this->alpha]);
        $this->content('missions', ['name' => 'Test Draft Mission', 'category' => 'abroad_consulate', 'location_country_id' => $this->alpha], false);

        $this->cover($this->embassy, $this->alpha, 'resident');
        $this->cover($this->embassy, $this->gamma, 'non_resident');
        $this->cover($this->nonResident, $this->gamma, 'non_resident');
    }

    public function testHierarchyPagesAreAvailable(): void
    {
        foreach ([
            'missions', 'missions/abroad', 'missions/in-south-sudan',
            'missions/abroad/embassies', 'missions/abroad/consulates', 'missions/abroad/permanent-missions', 'missions/abroad/non-resident-missions',
            'missions/in-south-sudan/embassies', 'missions/in-south-sudan/international-agencies',
        ] as $path) {
            $this->get('en/' . $path)->assertOK();
        }
    }

    public function testCategoryListsOnlyPublishedMissionsOfThatType(): void
    {
        $embassies = $this->get('en/missions/abroad/embassies');
        $embassies->assertSee('Test Embassy Alpha');
        $embassies->assertDontSee('Test Non-resident Mission');

        $this->get('en/missions/abroad/consulates')->assertDontSee('Test Draft Mission');
        $this->get('en/missions/in-south-sudan/embassies')->assertSee('Test Foreign Embassy');
    }

    public function testCategoryShowsFlagCardsWithCountryNamesAndSearch(): void
    {
        $this->db->table('countries')->insert(['iso_code' => 'JP', 'name' => 'Japan', 'is_demo' => 1]);
        $japan = (int) $this->db->insertID();
        $this->content('missions', ['name' => 'Embassy in Tokyo', 'category' => 'abroad_embassy', 'location_country_id' => $japan, 'city' => 'Tokyo']);

        $result = $this->get('en/missions/abroad/embassies');
        $result->assertSee('data-testid="mission-cards"');
        $result->assertSee('assets/flags/4x3/jp.svg');
        $result->assertSee('Japan');
        $result->assertSee('Also accredited to: Testland Gamma');
        $result->assertSee('Search by country');
        $result->assertSee('assets/js/mission-filter.js');
        // A test country without a flag still shows its name.
        $result->assertSee('Testland Alpha');
        $result->assertDontSee('assets/flags/4x3/xa.svg');
    }

    public function testEmbassyProfileListsEveryDetailAndConsulatesInTheCountry(): void
    {
        $this->content('missions', ['name' => 'Test Consulate Alpha', 'category' => 'abroad_consulate', 'location_country_id' => $this->alpha, 'city' => 'Port Alpha']);
        $this->content('missions', ['name' => 'Test Embassy Beta', 'category' => 'abroad_embassy', 'location_country_id' => $this->beta]);

        $alpha = $this->get('en/missions/profile/test-embassy-alpha');
        $alpha->assertSee('data-testid="mission-details"');
        $alpha->assertSee('Consulates in Testland Alpha');
        $alpha->assertSee('Test Consulate Alpha');
        $alpha->assertDontSee('Test Draft Mission');

        $beta = $this->get('en/missions/profile/test-embassy-beta');
        $beta->assertSee('To be confirmed');
        $beta->assertSee('No South Sudanese consulate is listed in Testland Beta.');
    }

    public function testProfileShowsTheFirstImageLargeAndTheRestSmaller(): void
    {
        $this->get('en/missions/profile/test-embassy-alpha')->assertDontSee('data-testid="mission-photos"');

        foreach (['Front of the embassy', 'Reception room', 'Garden'] as $i => $alt) {
            $this->db->table('media')->insert(['kind' => 'image', 'source' => 'upload', 'path' => 'test-' . $i . '.jpg', 'mime' => 'image/jpeg', 'size' => 1, 'title' => $alt, 'alt_text' => $alt]);
            $this->db->table('media_links')->insert(['media_id' => $this->db->insertID(), 'owner_type' => 'missions', 'owner_id' => $this->embassy, 'sort_order' => $i]);
        }

        $page = $this->get('en/missions/profile/test-embassy-alpha');
        $page->assertSee('data-testid="mission-photos"');
        $this->assertMatchesRegularExpression('/mission-gallery-main">\s*<a[^>]*>\s*<img[^>]*alt="Front of the embassy"/', $page->getBody());
        $this->assertSame(2, substr_count($page->getBody(), 'mission-gallery-thumb'));
    }

    public function testUnknownCategoryIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('en/missions/abroad/trade-offices');
    }

    public function testProfileShowsDetailsCoverageAndRelatedMissions(): void
    {
        $this->content('articles', ['category' => 'mission_activity', 'title' => 'Alpha activity', 'mission_id' => $this->embassy]);

        $result = $this->get('en/missions/profile/test-embassy-alpha');
        $result->assertSee('Test Embassy Alpha');
        $result->assertSee('Alpha City');
        $result->assertSee('Test Head');
        $result->assertSee('a@example.invalid');
        $result->assertSee('Alpha activity');

        $html = $result->getBody();
        $this->assertMatchesRegularExpression('#data-testid="countries-served".*Testland Alpha.*Resident mission.*Testland Gamma.*Non-resident#s', $html);
        $this->assertMatchesRegularExpression('#data-testid="related-missions".*Test Non-resident Mission#s', $html);
    }

    public function testDraftMissionProfileIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('en/missions/profile/test-draft-mission');
    }

    public function testCountryPageListsEveryMissionServingIt(): void
    {
        $html = $this->get('en/missions/countries/XC')->getBody();

        $this->assertStringContainsString('Test Embassy Alpha', $html);
        $this->assertStringContainsString('Test Non-resident Mission', $html);
        $this->assertStringContainsString('Non-resident: serves this country from Testland Alpha', $html);
    }

    public function testCountryPageShowsTheCountrysRepresentationInSouthSudan(): void
    {
        $html = $this->get('en/missions/countries/XA')->getBody();

        $this->assertMatchesRegularExpression('#data-testid="serving-missions".*Test Embassy Alpha.*Resident mission#s', $html);
        $this->assertMatchesRegularExpression('#data-testid="representation".*Test Foreign Embassy#s', $html);
    }

    public function testCountryWithoutMissionsSaysSo(): void
    {
        $result = $this->get('en/missions/countries/XB');

        $result->assertSee('No South Sudanese mission is currently listed for Testland Beta.');
    }

    public function testFinderGoesStraightToTheOnlyMissionServingACountry(): void
    {
        $this->get('en/missions/finder?country=xa')->assertRedirectTo(site_url('en/missions/profile/test-embassy-alpha'));
    }

    public function testFinderRedirectsToTheCountryPage(): void
    {
        $this->get('en/missions/finder?country=xc')->assertRedirectTo(site_url('en/missions/countries/XC'));
        $this->get('en/missions/finder?country=xb')->assertRedirectTo(site_url('en/missions/countries/XB'));
        $this->get('en/missions/finder?country=<script>')->assertRedirectTo(site_url('en/missions/countries'));
    }

    public function testFinderOffersOnlyCountriesWithPublishedMissions(): void
    {
        $html = $this->get('en')->getBody();

        $this->assertStringContainsString('<option value="XA">Testland Alpha</option>', $html);
        $this->assertStringContainsString('<option value="XC">Testland Gamma</option>', $html);
        $this->assertStringNotContainsString('Testland Beta</option>', $html);
    }

    public function testOptionalFieldsAreNotRequired(): void
    {
        $id = $this->content('missions', ['name' => 'Minimal mission', 'category' => 'abroad_permanent_mission']);

        $this->get('en/missions/profile/minimal-mission')->assertOK();
        $this->assertNotNull($id);
    }

    private function cover(int $missionId, int $countryId, string $coverage): void
    {
        $this->db->table('mission_coverage')->insert(['mission_id' => $missionId, 'country_id' => $countryId, 'coverage' => $coverage]);
    }
}
