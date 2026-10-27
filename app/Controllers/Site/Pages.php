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
    public function section(string $section): string
    {
        $tree = $this->publishedTree($section);

        return view('site/pages/section', [
            'section'      => $section,
            'heading'      => $this->sectionHeading($section),
            'tree'         => $tree,
            'expandable'   => $section === 'services',
        ]);
    }

    public function show(string $section, string ...$segments): string
    {
        $path = implode('/', $segments);
        $page = $this->requireRow($this->published('pages')->where(['section' => $section, 'path' => $path])->get()->getRowArray());

        $ancestors = $this->ancestors($page);
        if ($ancestors === null) {
            $this->notFound();
        }

        $children = $this->published('pages')->where('parent_id', $page['id'])->orderBy('sort_order')->orderBy('title')->get()->getResultArray();
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
        ]);
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
    private function ancestors(array $page): ?array
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
