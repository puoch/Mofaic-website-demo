<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\StaffTestCase;

/**
 * Site search behind the search button in the main navigation.
 */
final class SiteSearchTest extends StaffTestCase
{
    public function testEmptySearchShowsOnlyTheForm(): void
    {
        $html = $this->get('en/search')->getBody();

        $this->assertStringContainsString('id="search-q"', $html);
        $this->assertStringNotContainsString('data-testid="result-count"', $html);
    }

    public function testFindsPublishedArticlesPagesAndMissionsOnly(): void
    {
        $this->content('articles', ['category' => 'news', 'title' => 'Scholarship news item']);
        $this->content('articles', ['category' => 'statement', 'title' => 'Statement on scholarship awards']);
        $this->content('articles', ['category' => 'news', 'title' => 'Unpublished scholarship draft'], false);
        $this->content('pages', ['section' => 'services', 'title' => 'Scholarship guidance']);
        $this->content('missions', ['name' => 'Scholarship liaison office', 'category' => 'abroad_embassy']);
        $this->content('articles', ['category' => 'news', 'title' => 'Trade talks']);

        $html = $this->get('en/search?q=scholarship')->getBody();

        $this->assertStringContainsString('Scholarship news item', $html);
        $this->assertStringContainsString('Statement on scholarship awards', $html);
        $this->assertStringContainsString('Scholarship guidance', $html);
        $this->assertStringContainsString('Scholarship liaison office', $html);
        $this->assertStringNotContainsString('Unpublished scholarship draft', $html);
        $this->assertStringNotContainsString('Trade talks', $html);
        $this->assertStringContainsString('4 results', $html);
        $this->assertMatchesRegularExpression('#href="[^"]*/en/news-press/statements/[^"]+">Statement on scholarship awards#', $html);
    }

    public function testFindsMissionsByHostCountry(): void
    {
        $host = $this->country('XA', 'Testland');
        $this->content('missions', ['name' => 'Test embassy alpha', 'category' => 'abroad_embassy', 'location_country_id' => $host]);

        $this->assertStringContainsString('Test embassy alpha', $this->get('en/search?q=testland')->getBody());
    }

    public function testNoMatchSaysSo(): void
    {
        $html = $this->get('en/search?q=' . rawurlencode('<b>zzz</b>'))->getBody();

        $this->assertStringContainsString('data-testid="search-empty"', $html);
        $this->assertStringNotContainsString('<b>zzz</b>', $html);
    }
}
