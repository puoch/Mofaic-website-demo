<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\StaffTestCase;

/**
 * The public layout: identical, fixed navigation on every public page
 * (PRI-09, NAV-01), language and direction attributes (PRI-07) and locally
 * served assets (PRI-04).
 */
final class PublicSiteTest extends StaffTestCase
{
    /**
     * @return list<array{string}>
     */
    public static function publicPaths(): array
    {
        return [
            ['en'],
            ['en/missions'],
            ['en/missions/abroad'],
            ['en/missions/abroad/embassies'],
            ['en/missions/abroad/consulates'],
            ['en/missions/abroad/permanent-missions'],
            ['en/missions/abroad/non-resident-missions'],
            ['en/missions/in-south-sudan'],
            ['en/missions/in-south-sudan/embassies'],
            ['en/missions/in-south-sudan/international-agencies'],
            ['en/about-mfaic'],
            ['en/about-south-sudan'],
            ['en/services'],
            ['en/news-press'],
            ['en/diaspora'],
            ['en/contacts'],
            ['en/archives'],
        ];
    }

    /**
     * @dataProvider publicPaths
     */
    public function testEveryPublicPageHasTheSameFixedNavigation(string $path): void
    {
        $result = $this->get($path);
        $result->assertOK();

        $html = $result->getBody();
        $this->assertMatchesRegularExpression('/<header class="site-header sticky-top"/', $html);
        $this->assertSame($this->topLevelNavKeys($this->get('en')->getBody()), $this->topLevelNavKeys($html));
        $this->assertSame(
            ['home', 'missions', 'aboutMfaic', 'aboutSouthSudan', 'services', 'newsPress', 'diaspora', 'contacts'],
            $this->topLevelNavKeys($html),
        );
    }

    /**
     * @dataProvider publicPaths
     */
    public function testEveryPublicPageDeclaresLanguageAndDirection(string $path): void
    {
        $this->assertStringContainsString('<html lang="en" dir="ltr">', $this->get($path)->getBody());
    }

    /**
     * @dataProvider publicPaths
     */
    public function testNoAssetIsLoadedFromAnotherHost(string $path): void
    {
        preg_match_all('/<(?:link|script)[^>]+(?:href|src)="([^"]+)"/', $this->get($path)->getBody(), $m);

        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $url) {
            $this->assertStringStartsWith(base_url(), $url, "Asset from another host: {$url}");
        }
    }

    public function testRootRedirectsToTheDefaultLanguage(): void
    {
        $this->get('/')->assertRedirectTo(site_url('en'));
    }

    public function testUnsupportedLanguageIsNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('fr/missions');
    }

    public function testArchivesIsLinkedFromTheFooterNotTheTopBar(): void
    {
        $html = $this->get('en')->getBody();

        $this->assertNotContains('archives', $this->topLevelNavKeys($html));
        $this->assertMatchesRegularExpression('#<footer.*href="[^"]*/en/archives".*</footer>#s', $html);
    }

    public function testNonProductionPagesAreMarkedAndNotIndexed(): void
    {
        $html = $this->get('en')->getBody();

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringContainsString('This is not an official website', $html);
    }

    public function testSecurityHeadersAreSent(): void
    {
        $result = $this->get('en');

        $this->assertSame('SAMEORIGIN', $result->response()->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $result->response()->getHeaderLine('X-Content-Type-Options'));
        // The Content-Security-Policy header is added when the response is
        // sent, which feature tests skip, so check that it is switched on.
        $this->assertTrue(config('App')->CSPEnabled);
    }

    /**
     * @return list<string>
     */
    private function topLevelNavKeys(string $html): array
    {
        preg_match_all('/<a class="nav-link[^"]*" data-nav-key="([a-zA-Z]+)"/', $html, $m);

        return $m[1];
    }
}
