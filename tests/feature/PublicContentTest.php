<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\StructureSeeder;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\StaffTestCase;

/**
 * Public pages of the content modules: About, Services, News / Press,
 * Archives and the home page areas (brief section 2).
 */
final class PublicContentTest extends StaffTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StructureSeeder::class);
    }

    public function testStructureMatchesTheConfirmedSections(): void
    {
        $paths = fn (string $section): array => array_column(
            $this->db->table('pages')->where('section', $section)->orderBy('path')->get()->getResultArray(),
            'path',
        );

        $this->assertSame(
            ['ethnic-cultural-diversity', 'national-symbols', 'people-languages-religions', 'basic-information'],
            array_values(array_intersect(['ethnic-cultural-diversity', 'national-symbols', 'people-languages-religions', 'basic-information'], $paths('about-south-sudan'))),
        );
        $this->assertCount(4, $paths('about-south-sudan'));
        $this->assertSame([
            'citizens', 'citizens/consular-services', 'citizens/document-authentication', 'citizens/scholarships',
            'citizens/traveling-abroad', 'citizens/traveling-abroad/travel-advice-notices', 'citizens/traveling-abroad/visa-information',
            'foreigners', 'foreigners/entry-requirements', 'foreigners/visa-requirements',
        ], $paths('services'));
        $this->assertContains('leadership', $paths('about-mfaic'));
        $this->assertContains('organogram', $paths('about-mfaic'));
    }

    public function testStructurePagesStartUnpublishedAndEmpty(): void
    {
        $this->assertSame(0, $this->db->table('pages')->where('status', 'published')->countAllResults());
        $this->assertSame(0, $this->db->table('pages')->where('body IS NOT NULL')->countAllResults());

        $this->expectException(PageNotFoundException::class);
        $this->get('en/about-mfaic/leadership');
    }

    public function testStructureSeederCanRunTwice(): void
    {
        $before = $this->db->table('pages')->countAllResults();
        $this->seed(StructureSeeder::class);

        $this->assertSame($before, $this->db->table('pages')->countAllResults());
    }

    public function testPublishedPageShowsTextLinksAndChildren(): void
    {
        $this->publishPage('citizens', ['body' => "## Help\n\nInformation text.", 'external_links' => "Official portal | https://example.org/portal\nnot a link"]);
        $this->publishPage('citizens/consular-services');

        $result = $this->get('en/services/citizens');
        $result->assertOK();
        $result->assertSee('<h2>Help</h2>', null);
        $result->assertSee('Official portal');
        $result->assertSee('Consular Services');
        $this->assertStringNotContainsString('not a link', $result->getBody());
    }

    public function testChildOfAnUnpublishedPageIsHidden(): void
    {
        $this->publishPage('citizens/consular-services');

        $this->expectException(PageNotFoundException::class);
        $this->get('en/services/citizens/consular-services');
    }

    public function testServicesStartWithExpandableCitizensAndForeigners(): void
    {
        $this->publishPage('citizens');
        $this->publishPage('foreigners');
        $this->publishPage('foreigners/visa-requirements');

        $html = $this->get('en/services')->getBody();

        $this->assertSame(2, substr_count($html, '<details class="service-category"'));
        $this->assertMatchesRegularExpression('/Citizens.*Foreigners.*Visa Requirements/s', $html);
    }

    public function testEditorTextCannotInjectScripts(): void
    {
        $this->publishPage('citizens', ['body' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

        $html = $this->get('en/services/citizens')->getBody();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testOrganogramShowsTheUnitTree(): void
    {
        $this->publishPage('organogram', [], 'about-mfaic');
        service('content')->save('org_units', null, ['name' => 'Unit A (test)', 'head_title' => 'Head A'], null);
        $parent = (int) $this->db->table('org_units')->where('name', 'Unit A (test)')->get()->getRow()->id;
        service('content')->save('org_units', null, ['name' => 'Unit B (test)', 'parent_id' => $parent], null);

        $html = $this->get('en/about-mfaic/organogram')->getBody();

        $this->assertMatchesRegularExpression('#<details open><summary class="org-unit"><span class="org-name">Unit A \(test\)</span> <span class="org-head">Head A</span></summary><ul class="org-tree"><li><span class="org-unit"><span class="org-name">Unit B \(test\)#', $html);
    }

    public function testNewsIsListedNewestFirstWithSearchAndDateFilters(): void
    {
        $old = $this->content('articles', ['category' => 'news', 'title' => 'Older trade item']);
        $new = $this->content('articles', ['category' => 'news', 'title' => 'Newer health item']);
        $this->content('articles', ['category' => 'statement', 'title' => 'A statement']);
        $this->content('articles', ['category' => 'news', 'title' => 'Unpublished item'], false);
        $this->db->table('articles')->where('id', $old)->update(['published_at' => '2026-01-10 09:00:00']);
        $this->db->table('articles')->where('id', $new)->update(['published_at' => '2026-03-10 09:00:00']);

        $html = $this->get('en/news-press/news')->getBody();
        $this->assertMatchesRegularExpression('/Newer health item.*Older trade item/s', $html);
        $this->assertStringNotContainsString('A statement', $html);
        $this->assertStringNotContainsString('Unpublished item', $html);

        $this->assertStringNotContainsString('Newer health item', $this->get('en/news-press/news?q=trade')->getBody());
        $this->assertStringNotContainsString('Older trade item', $this->get('en/news-press/news?from=2026-02-01')->getBody());
        $this->assertStringNotContainsString('Newer health item', $this->get('en/news-press/news?to=2026-02-01')->getBody());
        $this->assertStringContainsString('2 results', $this->get('en/news-press/news?from=not-a-date')->getBody());
    }

    /**
     * PD-15: the keyword search finds words in the summary and text, requires
     * every word and matches word beginnings.
     * On MySQL this goes through the full-text index; on SQLite it is a plain
     * text match, which gives the same results for these words.
     */
    public function testKeywordSearchCoversSummaryAndTextAndRequiresEveryWord(): void
    {
        $this->content('articles', ['category' => 'news', 'title' => 'Item one', 'summary' => 'Talks with traders in Juba']);
        $this->content('articles', ['category' => 'news', 'title' => 'Item two', 'body' => 'Scholarship announcement for students']);
        $this->content('articles', ['category' => 'news', 'title' => 'Item three', 'body' => 'Scholarship results']);

        $search = fn (string $q): string => $this->get('en/news-press/news?q=' . rawurlencode($q))->getBody();

        $html = $search('trader');
        $this->assertStringContainsString('Item one', $html);
        $this->assertStringNotContainsString('Item two', $html);

        $html = $search('scholarship announcement');
        $this->assertStringContainsString('Item two', $html);
        $this->assertStringNotContainsString('Item three', $html);

        // A short word the full-text index skips still finds a match.
        $this->assertStringContainsString('Item one', $search('Ju'));
    }

    public function testNewsListIsPaginated(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->content('articles', ['category' => 'news', 'title' => "Item {$i}"]);
        }

        $this->assertStringContainsString('Page 1 of 2', $this->get('en/news-press/news')->getBody());
        $this->assertSame(2, substr_count($this->get('en/news-press/news?page=2')->getBody(), 'class="article-item"'));
    }

    public function testStatementShowsDownloadsAndTags(): void
    {
        $id = $this->content('articles', ['category' => 'statement', 'title' => 'Press statement', 'tags' => 'Trade']);
        $this->db->table('media')->insert(['kind' => 'document', 'source' => 'upload', 'path' => 'uploads/test.pdf', 'title' => 'Full statement', 'size' => 1048576, 'mime' => 'application/pdf']);
        $this->db->table('media_links')->insert(['media_id' => $this->db->insertID(), 'owner_type' => 'articles', 'owner_id' => $id, 'sort_order' => 1]);

        $result = $this->get('en/news-press/statements/press-statement');
        $result->assertSee('Full statement');
        $result->assertSee('(PDF, 1.0 MB)');
        $result->assertSee('news-press/tags/trade', null);

        $this->assertStringContainsString('Press statement', $this->get('en/news-press/tags/trade')->getBody());
    }

    public function testArticleShowsTheFirstPhotoLargeAndInLists(): void
    {
        $id = $this->content('articles', ['category' => 'news', 'title' => 'News with photos']);
        $this->photos('articles', $id, ['Minister at the signing', 'Delegations', 'Press conference']);

        $page = $this->get('en/news-press/news/news-with-photos')->getBody();
        $this->assertMatchesRegularExpression('/photo-gallery-main">\s*<figure[^>]*>\s*<a[^>]*>\s*<img[^>]*alt="Minister at the signing"/', $page);
        $this->assertSame(2, substr_count($page, 'photo-gallery-thumb'));

        $list = $this->get('en/news-press/news')->getBody();
        $this->assertStringContainsString('article-item has-photo', $list);
        $this->assertStringContainsString('src="' . base_url('uploads/test-0.jpg') . '"', $list);
    }

    public function testAlbumShowsItsPhotos(): void
    {
        $id = $this->content('galleries', ['title' => 'Photo album']);
        $this->photos('galleries', $id, ['First photo', 'Second photo']);

        $album = $this->get('en/news-press/gallery/photo-album')->getBody();
        $this->assertMatchesRegularExpression('/photo-gallery-main">\s*<figure[^>]*>\s*<a[^>]*>\s*<img[^>]*alt="First photo"/', $album);
        $this->assertSame(1, substr_count($album, 'photo-gallery-thumb'));
        $this->assertStringContainsString(base_url('uploads/test-0.jpg'), $this->get('en/news-press/gallery')->getBody());
    }

    public function testNewsUrlMustMatchItsCategory(): void
    {
        $this->content('articles', ['category' => 'statement', 'title' => 'Only a statement']);

        $this->expectException(PageNotFoundException::class);
        $this->get('en/news-press/news/only-a-statement');
    }

    public function testGalleryAndSocialMedia(): void
    {
        $this->content('galleries', ['title' => 'Test album']);
        service('content')->save('social_accounts', null, ['platform' => 'Example', 'label' => 'Official account (test)', 'url' => 'https://example.org/account', 'is_active' => 1], null);
        service('content')->save('social_accounts', null, ['platform' => 'Example', 'label' => 'Hidden account', 'url' => 'https://example.org/hidden', 'is_active' => 0], null);

        $this->get('en/news-press/gallery')->assertSee('Test album');
        $this->get('en/news-press/gallery/test-album')->assertOK();

        $social = $this->get('en/news-press/social-media');
        $social->assertSee('Official account (test)');
        $social->assertDontSee('Hidden account');
    }

    public function testArchiveKeepsLinksToNewsAndGalleries(): void
    {
        $article = $this->content('articles', ['category' => 'news', 'title' => 'Linked news']);
        $gallery = $this->content('galleries', ['title' => 'Linked album']);
        $this->content('archives', ['title' => 'Archive entry', 'related_article_id' => $article, 'related_gallery_id' => $gallery]);

        $this->get('en/archives')->assertSee('Archive entry');
        $result = $this->get('en/archives/archive-entry');
        $result->assertSee('Linked news');
        $result->assertSee('Linked album');
    }

    public function testHomePageAreas(): void
    {
        $this->content('articles', ['category' => 'news', 'title' => 'Featured item', 'is_featured' => 1]);
        $this->content('articles', ['category' => 'news', 'title' => 'Engagement item', 'tags' => "Minister's engagements"]);
        $this->content('articles', ['category' => 'mission_activity', 'title' => 'Activity item']);
        service('content')->save('quick_links', null, ['label' => 'Test quick link', 'url' => 'https://example.org', 'is_active' => 1], null);

        $html = $this->get('en')->getBody();

        $this->assertMatchesRegularExpression('#data-testid="featured-news".*Featured item#s', $html);
        $this->assertMatchesRegularExpression('#data-testid="minister-engagements".*Engagement item.*data-testid="mission-activities"#s', $html);
        $this->assertMatchesRegularExpression('#data-testid="mission-activities".*Activity item.*data-testid="quick-links"#s', $html);
        $this->assertStringContainsString('Test quick link', $html);
    }

    public function testEmergencyContactsAreHiddenUntilApprovedTextIsEntered(): void
    {
        $this->assertStringNotContainsString('data-testid="emergency-contacts"', $this->get('en')->getBody());

        $block = $this->db->table('site_blocks')->where('block_key', 'emergency_contacts')->get()->getRowArray();
        service('content')->save('site_blocks', (int) $block['id'], ['body' => 'Approved emergency text (test)'], null);

        $this->assertStringContainsString('Approved emergency text (test)', $this->get('en')->getBody());
    }

    public function testContactsShowOnlyMinistryEnteredInformation(): void
    {
        $page = $this->get('en/contacts');
        $page->assertOK();
        $this->assertStringNotContainsString('data-testid="contact-information"', $page->getBody());
        $this->assertStringNotContainsString('<form', $page->getBody(), 'The inquiry form is Gate 4.');

        $block = $this->db->table('site_blocks')->where('block_key', 'contact_information')->get()->getRowArray();
        service('content')->save('site_blocks', (int) $block['id'], ['body' => 'Contact text entered by staff (test)'], null);

        $this->assertStringContainsString('Contact text entered by staff (test)', $this->get('en/contacts')->getBody());
    }

    /**
     * @param array<string, mixed> $values
     */
    private function publishPage(string $path, array $values = [], string $section = 'services'): void
    {
        $page = $this->db->table('pages')->where(['section' => $section, 'path' => $path])->get()->getRowArray();
        service('content')->save('pages', (int) $page['id'], $values + ['title' => $page['title']], null);
        service('content')->publish('pages', (int) $page['id'], null);
    }

    /**
     * @param list<string> $descriptions
     */
    private function photos(string $ownerType, int $ownerId, array $descriptions): void
    {
        foreach ($descriptions as $i => $alt) {
            $this->db->table('media')->insert(['kind' => 'image', 'source' => 'upload', 'path' => 'uploads/test-' . $i . '.jpg', 'mime' => 'image/jpeg', 'size' => 1, 'title' => $alt, 'alt_text' => $alt]);
            $this->db->table('media_links')->insert(['media_id' => $this->db->insertID(), 'owner_type' => $ownerType, 'owner_id' => $ownerId, 'sort_order' => $i]);
        }
    }
}
