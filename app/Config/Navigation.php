<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Public site navigation.
 *
 * $primary is the top-level navigation in the order the project owner set on
 * 2026-11-10: HOME, MFA & IC, MISSIONS, SERVICES, SSD DIASPORA, MEDIA/PRESS.
 * CONTACTS and ABOUT SOUTH SUDAN moved to the footer ($footer), and a search
 * button follows the last item. Do not reorder or rename it without sign-off.
 * The MISSIONS children are the confirmed hierarchy and must not be
 * restructured. The menu shows no dropdown (2026-11-11): the children are
 * reached through the buttons on the Missions page and listed on the sitemap.
 *
 * ARCHIVES placement is undecided (brief section 6, item 2). It is held in
 * $archivesPlacement so the Ministry's decision is a configuration change.
 */
class Navigation extends BaseConfig
{
    /**
     * Each item: key (language line Site.nav.<key>), path (relative to the
     * locale prefix) and optional children.
     *
     * @var list<array{key: string, path: string, children?: list<array{key: string, path?: string, children?: list<array{key: string, path: string}>}>}>
     */
    public array $primary = [
        ['key' => 'home', 'path' => ''],
        ['key' => 'aboutMfaic', 'path' => 'about-mfaic'],
        [
            'key'      => 'missions',
            'path'     => 'missions',
            'children' => [
                [
                    'key'      => 'missionsAbroad',
                    'path'     => 'missions/abroad',
                    'children' => [
                        ['key' => 'abroadEmbassies', 'path' => 'missions/abroad/embassies'],
                        ['key' => 'abroadConsulates', 'path' => 'missions/abroad/consulates'],
                        ['key' => 'abroadPermanentMissions', 'path' => 'missions/abroad/permanent-missions'],
                        ['key' => 'abroadNonResidentMissions', 'path' => 'missions/abroad/non-resident-missions'],
                    ],
                ],
                [
                    'key'      => 'missionsInSouthSudan',
                    'path'     => 'missions/in-south-sudan',
                    'children' => [
                        ['key' => 'foreignEmbassies', 'path' => 'missions/in-south-sudan/embassies'],
                        ['key' => 'foreignInternationalAgencies', 'path' => 'missions/in-south-sudan/international-agencies'],
                    ],
                ],
            ],
        ],
        ['key' => 'services', 'path' => 'services'],
        ['key' => 'diaspora', 'path' => 'diaspora'],
        ['key' => 'newsPress', 'path' => 'news-press'],
    ];

    /**
     * Where ARCHIVES is linked from:
     *  - 'footer'  : footer only (proposed default PD-04, not approved)
     *  - 'primary' : top navigation, directly after the item named in $archivesAfter
     *
     * @var 'footer'|'primary'
     */
    public string $archivesPlacement = 'footer';

    public string $archivesAfter = 'newsPress';

    public string $archivesPath = 'archives';

    /**
     * Pages linked from the footer that are no longer in the top navigation.
     *
     * Each item: label (language line) and path (relative to the locale prefix).
     *
     * @var list<array{label: string, path: string}>
     */
    public array $footer = [
        ['label' => 'Site.footerAboutSouthSudan', 'path' => 'about-south-sudan'],
        ['label' => 'Site.footerContacts', 'path' => 'contacts'],
    ];
}
