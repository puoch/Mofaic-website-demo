<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Navigation;

/**
 * Guards the approved navigation and missions hierarchy (brief section 2,
 * NAV-01 and MIS-01). A failure here means the approved structure changed.
 */
final class NavigationConfigTest extends CIUnitTestCase
{
    public function testTopNavigationIsTheApprovedOrder(): void
    {
        $labels = array_map(
            static fn (array $item): string => lang('Site.nav.' . $item['key']),
            config(Navigation::class)->primary,
        );

        $this->assertSame(
            ['HOME', 'MISSIONS', 'ABOUT MFA&IC', 'ABOUT SOUTH SUDAN', 'SERVICES', 'NEWS / PRESS', 'SSD DIASPORA', 'CONTACTS'],
            $labels,
        );
    }

    public function testMissionsHierarchyIsExactlyAsConfirmed(): void
    {
        $missions = config(Navigation::class)->primary[1];

        $tree = [];
        foreach ($missions['children'] as $group) {
            $tree[lang('Site.nav.' . $group['key'])] = array_map(
                static fn (array $child): string => lang('Site.nav.' . $child['key']),
                $group['children'],
            );
        }

        $this->assertSame([
            'Missions Abroad' => ['Embassies', 'Consulates', 'Permanent Missions', 'Non-resident Missions'],
            'Foreign Missions in South Sudan' => ['Embassies', 'International Agencies'],
        ], $tree);
    }

    public function testArchivesIsNotInTopNavigationByDefault(): void
    {
        $this->assertSame('footer', config(Navigation::class)->archivesPlacement);
        $this->assertNotContains('archives', array_column(primary_navigation(), 'key'));
    }

    public function testArchivesPlacementInTopNavigationIsAConfigurationChange(): void
    {
        $config                    = config(Navigation::class);
        $config->archivesPlacement = 'primary';
        $config->archivesAfter     = 'newsPress';

        $keys = array_column(primary_navigation(), 'key');

        $this->assertSame(array_search('newsPress', $keys, true) + 1, array_search('archives', $keys, true));
        $this->assertCount(9, $keys);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->resetServices();
        \CodeIgniter\Config\Factories::reset('config');
    }
}
