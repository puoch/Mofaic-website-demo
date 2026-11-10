<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use Config\ContentTypes;

/**
 * About MFA&IC, About South Sudan and Services pages.
 *
 * A page is shown only when it and every page above it are published.
 */
class Pages extends SiteController
{
    /**
     * Buttons at the top of a section page, in the sponsor's order:
     * section => [path below the locale => language line].
     * A path inside the section that has no published page yet shows a
     * "being prepared" page; other paths (archives, contacts) are their own pages.
     */
    public const SECTION_CHOICES = [
        'services' => [
            // Citizens and Foreigners are CMS pages; Important links lists the quick links.
            'services/citizens'        => 'Public.serviceCitizens',
            'services/foreigners'      => 'Public.serviceForeigners',
            'services/important-links' => 'Public.quickLinks',
        ],
        'about-mfaic' => [
            'about-mfaic/minister'                 => 'Public.mfaMinister',
            'about-mfaic/deputy-minister'          => 'Public.mfaDeputyMinister',
            'about-mfaic/undersecretary'           => 'Public.mfaUndersecretary',
            'about-mfaic/competences'              => 'Public.mfaCompetences',
            'about-mfaic/ministry-history'         => 'Public.mfaHistory',
            'about-mfaic/organogram'               => 'Public.mfaOrganisationalStructure',
            'about-mfaic/diplomatic-protocol'      => 'Public.mfaDiplomaticProtocol',
            'archives'                             => 'Public.mfaDiplomaticArchive',
            'contacts'                             => 'Public.mfaContact',
        ],
    ];

    /** @var array<string, string> section => language line naming its buttons for screen readers */
    private const CHOICES_LABEL = [
        'services'    => 'Public.serviceChoices',
        'about-mfaic' => 'Public.mfaChoices',
    ];

    /**
     * Buttons shown on a Services page for its sub pages: path => [sub path => language line].
     * Shown in alphabetical order of the label.
     */
    public const SERVICE_SUB_CHOICES = [
        'citizens' => [
            'citizens/consular-services'       => 'Public.serviceConsular',
            'citizens/document-authentication' => 'Public.serviceDocumentAuthentication',
            'citizens/scholarships'            => 'Public.serviceScholarships',
            'citizens/traveling-abroad'        => 'Public.serviceTravelingAbroad',
        ],
        'foreigners' => [
            'foreigners/overflight'            => 'Public.serviceOverflight',
            'foreigners/travel-to-south-sudan' => 'Public.serviceTravelToSouthSudan',
        ],
    ];

    public function section(string $section): string
    {
        $choices = self::SECTION_CHOICES[$section] ?? [];
        $tree    = array_values(array_filter(
            $this->publishedTree($section),
            static fn (array $page): bool => ! isset($choices[$section . '/' . $page['path']]),
        ));

        return view('site/pages/section', [
            'section'      => $section,
            'heading'      => $this->sectionHeading($section),
            'tree'         => $tree,
            'expandable'   => $section === 'services',
            'choices'      => array_map(static fn (string $line): string => lang($line), $choices),
            'choicesLabel' => lang(self::CHOICES_LABEL[$section] ?? 'Public.serviceChoices'),
        ]);
    }

    /**
     * Services > Important links: the active quick links, managed under Quick links in the admin.
     */
    public function importantLinks(): string
    {
        return view('site/pages/important_links', [
            'heading' => $this->sectionHeading('services'),
            'links'   => db_connect()->table('quick_links')->where(['locale' => $this->locale(), 'is_active' => 1])->orderBy('sort_order')->get()->getResultArray(),
        ]);
    }

    public function show(string $section, string ...$segments): string
    {
        $path = implode('/', $segments);
        $page = $this->published('pages')->where(['section' => $section, 'path' => $path])->get()->getRowArray();

        $ancestors = $page === null ? null : self::ancestors($page);

        // Every button leads somewhere, even before the Ministry publishes
        // the page (or a page above it): show the title and a "being prepared" note.
        $label = $this->buttonLabel($section, $path);
        if (($page === null || $ancestors === null) && $label !== null) {
            $crumbs = [];
            for ($prefix = dirname($path); $prefix !== '.'; $prefix = dirname($prefix)) {
                array_unshift($crumbs, ['title' => $this->buttonLabel($section, $prefix) ?? $prefix, 'path' => $prefix]);
            }

            return view('site/pages/show', [
                'section'   => $section,
                'heading'   => $this->sectionHeading($section),
                'page'      => ['title' => $label, 'summary' => null],
                'bodyHtml'  => '',
                'links'     => [],
                'media'     => [],
                'ancestors' => $crumbs,
                'children'  => [],
                'orgUnits'  => null,
                'choices'   => $this->subChoices($section, $path),
                'pending'   => true,
            ]);
        }

        $page = $this->requireRow($page);
        if ($ancestors === null) {
            $this->notFound();
        }

        $choices  = $this->subChoices($section, $path);
        $children = $this->published('pages')->where('parent_id', $page['id'])->orderBy('sort_order')->orderBy('title')->get()->getResultArray();
        $children = array_values(array_filter($children, static fn (array $child): bool => ! isset($choices[$child['path']])));
        $orgUnits = $section === 'about-mfaic' && $path === 'organogram' ? $this->orgTree() : null;

        return view('site/pages/show', [
            'section'   => $section,
            'heading'   => $this->sectionHeading($section),
            'page'      => $page,
            'bodyHtml'  => service('markdown')->toHtml($page['body']),
            'links'     => parse_external_links($page['external_links']),
            'media'     => service('media')->forOwner('pages', (int) $page['id']),
            'ancestors' => $ancestors,
            'children'  => $children,
            'orgUnits'  => $orgUnits,
            'choices'   => $choices,
        ]);
    }

    /**
     * The button label for a page path in a section, or null if it is not a button.
     */
    private function buttonLabel(string $section, string $path): ?string
    {
        $line = self::SECTION_CHOICES[$section][$section . '/' . $path] ?? null;
        if ($line !== null) {
            return lang($line);
        }

        if ($section !== 'services') {
            return null;
        }

        foreach (self::SERVICE_SUB_CHOICES as $subChoices) {
            if (isset($subChoices[$path])) {
                return lang($subChoices[$path]);
            }
        }

        return null;
    }

    /**
     * @return array<string, string> Sub page path => label, A to Z
     */
    private function subChoices(string $section, string $path): array
    {
        if ($section !== 'services' || ! isset(self::SERVICE_SUB_CHOICES[$path])) {
            return [];
        }

        $choices = array_map(static fn (string $line): string => lang($line), self::SERVICE_SUB_CHOICES[$path]);
        asort($choices, SORT_NATURAL | SORT_FLAG_CASE);

        return $choices;
    }

    private function sectionHeading(string $section): string
    {
        return match ($section) {
            'about-mfaic'       => lang('Site.nav.aboutMfaic'),
            'about-south-sudan' => lang('Site.nav.aboutSouthSudan'),
            'services'          => lang('Site.nav.services'),
            default             => config(ContentTypes::class)->pageSections[$section] ?? $section,
        };
    }

    /**
     * @return list<array<string, mixed>> Published pages, each with 'children'
     */
    private function publishedTree(string $section, ?int $parentId = null): array
    {
        $builder = $this->published('pages')->where('section', $section)->orderBy('sort_order')->orderBy('title');
        $parentId === null ? $builder->where('parent_id IS NULL') : $builder->where('parent_id', $parentId);

        $pages = $builder->get()->getResultArray();
        foreach ($pages as &$page) {
            $page['children'] = $this->publishedTree($section, (int) $page['id']);
        }

        return $pages;
    }

    /**
     * Published ancestors from the top down, or null if any is unpublished.
     *
     * @param array<string, mixed> $page
     *
     * @return list<array<string, mixed>>|null
     */
    public static function ancestors(array $page): ?array
    {
        $chain = [];

        for ($parentId = $page['parent_id']; $parentId !== null; $parentId = $parent['parent_id']) {
            $parent = db_connect()->table('pages')->where('id', $parentId)->get()->getRowArray();

            if ($parent === null || $parent['status'] !== 'published') {
                return null;
            }

            array_unshift($chain, $parent);
        }

        return $chain;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function orgTree(?int $parentId = null, ?array $all = null, int $depth = 0): array
    {
        if ($depth > 20) {
            return [];
        }

        $all ??= db_connect()->table('org_units')->where('locale', $this->locale())->orderBy('sort_order')->orderBy('name')->get()->getResultArray();

        $level = [];
        foreach ($all as $unit) {
            if (($unit['parent_id'] === null ? null : (int) $unit['parent_id']) === $parentId) {
                $unit['children'] = $this->orgTree((int) $unit['id'], $all, $depth + 1);
                $level[]          = $unit;
            }
        }

        return $level;
    }
}
