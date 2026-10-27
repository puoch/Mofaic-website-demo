<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;
use Config\ContentTypes;

/**
 * Creates the confirmed page structure (brief section 2) as empty draft
 * pages, the "Minister's engagements" tag and the fixed home page notices.
 *
 * It adds titles only, never Ministry content. Pages stay unpublished until
 * an editor writes and publishes them. Safe to run in production and safe to
 * run again: existing rows are left alone.
 *
 *   php spark db:seed StructureSeeder
 */
class StructureSeeder extends Seeder
{
    /**
     * section => list of [slug, title, children]
     *
     * @var array<string, list<array{0: string, 1: string, 2?: list<array{0: string, 1: string, 2?: list<mixed>}>}>>
     */
    public const PAGES = [
        'about-mfaic' => [
            ['leadership', 'Leadership'],
            ['organogram', 'Organogram'],
            ['bilateral-relations', 'Bilateral Relations'],
            ['multilateral-relations', 'Multilateral Relations'],
            ['ministry-history', 'Ministry History'],
            ['diplomatic-code', 'Diplomatic Code'],
            ['heads-of-mission', 'Heads of Mission'],
        ],
        'about-south-sudan' => [
            ['basic-information', 'Basic Information'],
            ['national-symbols', 'National Symbols'],
            ['people-languages-religions', 'People, Languages and Religions'],
            ['ethnic-cultural-diversity', 'Ethnic and Cultural Diversity'],
        ],
        'services' => [
            ['citizens', 'Citizens', [
                ['traveling-abroad', 'Traveling Abroad', [
                    ['visa-information', 'Visa Information'],
                    ['travel-advice-notices', 'Travel Advice/Notices'],
                ]],
                ['consular-services', 'Consular Services'],
                ['scholarships', 'Scholarships'],
                ['document-authentication', 'Document Authentication'],
            ]],
            ['foreigners', 'Foreigners', [
                ['visa-requirements', 'Visa Requirements'],
                ['entry-requirements', 'Entry Requirements'],
            ]],
        ],
    ];

    public function run(): void
    {
        $locale = config('App')->defaultLocale;
        $types  = config(ContentTypes::class);

        foreach (self::PAGES as $section => $pages) {
            $this->pages($section, $pages, null, '', $locale);
        }

        service('content')->ensureTag($types->ministerEngagementTag, "Minister's engagements", true);

        foreach (array_keys($types->siteBlocks) as $key) {
            if ($this->db->table('site_blocks')->where(['block_key' => $key, 'locale' => $locale])->countAllResults() === 0) {
                $this->db->table('site_blocks')->insert(['block_key' => $key, 'locale' => $locale, 'body' => null]);
            }
        }
    }

    /**
     * @param list<array<int, mixed>> $pages
     */
    private function pages(string $section, array $pages, ?int $parentId, string $parentPath, string $locale): void
    {
        $now = Time::now('UTC')->toDateTimeString();

        foreach ($pages as $order => $page) {
            [$slug, $title] = $page;
            $path           = $parentPath === '' ? $slug : $parentPath . '/' . $slug;
            $existing       = $this->db->table('pages')->where(['locale' => $locale, 'section' => $section, 'path' => $path])->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('pages')->insert([
                    'locale'     => $locale,
                    'section'    => $section,
                    'parent_id'  => $parentId,
                    'slug'       => $slug,
                    'path'       => $path,
                    'title'      => $title,
                    'sort_order' => $order + 1,
                    'is_system'  => 1,
                    'status'     => 'draft',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $id = (int) $this->db->insertID();
            } else {
                $id = (int) $existing['id'];
            }

            if (! empty($page[2])) {
                $this->pages($section, $page[2], $id, $path, $locale);
            }
        }
    }
}
