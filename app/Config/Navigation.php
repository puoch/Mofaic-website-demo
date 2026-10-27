<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Public site navigation.
 *
 * $primary is the approved top-level navigation (brief section 2) in its
 * approved order. Do not reorder or rename it without sponsor sign-off.
 * The MISSIONS children are the confirmed hierarchy and must not be
 * restructured.
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
        ['key' => 'aboutMfaic', 'path' => 'about-mfaic'],
        ['key' => 'aboutSouthSudan', 'path' => 'about-south-sudan'],
        ['key' => 'services', 'path' => 'services'],
        ['key' => 'newsPress', 'path' => 'news-press'],
        ['key' => 'diaspora', 'path' => 'diaspora'],
        ['key' => 'contacts', 'path' => 'contacts'],
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
}
