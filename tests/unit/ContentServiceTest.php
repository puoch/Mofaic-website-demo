<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use Tests\Support\StaffTestCase;

/**
 * Revisions, restore, publishing log, slugs, page paths and tags (GOV-05,
 * GOV-06, NEWS-06).
 */
final class ContentServiceTest extends StaffTestCase
{
    public function testEverySaveStoresARevision(): void
    {
        $content = service('content');
        $id      = $content->save('articles', null, ['category' => 'news', 'title' => 'First title'], 5);
        $content->save('articles', $id, ['category' => 'news', 'title' => 'Second title'], 5);

        $revisions = $content->revisions('articles', $id);

        $this->assertSame([2, 1], array_map('intval', array_column($revisions, 'revision_number')));
        $this->assertSame('First title', json_decode($revisions[1]['snapshot'], true)['title']);
    }

    public function testRestoreBringsBackAnEarlierVersionAndIsLogged(): void
    {
        $content = service('content');
        $id      = $content->save('articles', null, ['category' => 'news', 'title' => 'Original', 'body' => 'Original text', 'tags' => 'Alpha'], 5);
        $content->save('articles', $id, ['category' => 'news', 'title' => 'Changed', 'body' => 'Changed text', 'tags' => 'Beta'], 5);

        $first = $content->revisions('articles', $id)[1];
        $content->restore('articles', $id, (int) $first['id'], 7);

        $record = $content->find('articles', $id);
        $this->assertSame('Original', $record['title']);
        $this->assertSame('Original text', $record['body']);
        $this->assertSame('Alpha', $record['tags']);

        $latest = $content->revisions('articles', $id)[0];
        $this->assertSame('3', (string) $latest['revision_number']);
        $this->assertSame('Restored from revision 1', $latest['note']);

        $event = $this->db->table('audit_events')->where('event', 'content.restored')->get()->getRowArray();
        $this->assertSame('7', (string) $event['actor_user_id']);
        $this->assertSame(1, json_decode($event['details'], true)['revision']);
    }

    public function testRestoreDoesNotChangePublishingStatus(): void
    {
        $content = service('content');
        $id      = $this->content('articles', ['category' => 'news', 'title' => 'One']);
        $content->save('articles', $id, ['category' => 'news', 'title' => 'Two'], null);
        $content->restore('articles', $id, (int) $content->revisions('articles', $id)[1]['id'], null);

        $this->assertSame('published', $content->find('articles', $id)['status']);
    }

    public function testPublishingIsLoggedAndKeepsTheFirstPublicationDate(): void
    {
        $content = service('content');
        $id      = $content->save('articles', null, ['category' => 'news', 'title' => 'News'], 3);

        $content->publish('articles', $id, 3);
        $first = $content->find('articles', $id)['published_at'];
        $content->unpublish('articles', $id, 3);
        $content->publish('articles', $id, 3);

        $this->assertSame($first, $content->find('articles', $id)['published_at']);
        $this->assertSame(
            ['content.created', 'content.published', 'content.unpublished', 'content.published'],
            array_column($this->db->table('audit_events')->orderBy('id')->get()->getResultArray(), 'event'),
        );
    }

    public function testSlugsAreCreatedFromTitlesAndKeptUnique(): void
    {
        $content = service('content');
        $a       = $content->save('articles', null, ['category' => 'news', 'title' => 'Same Title!'], null);
        $b       = $content->save('articles', null, ['category' => 'news', 'title' => 'Same Title!'], null);

        $this->assertSame('same-title', $content->find('articles', $a)['slug']);
        $this->assertSame('same-title-2', $content->find('articles', $b)['slug']);
    }

    public function testPagePathsFollowTheParentAndMoveWithIt(): void
    {
        $content = service('content');
        $parent  = $content->save('pages', null, ['section' => 'services', 'title' => 'Citizens'], null);
        $child   = $content->save('pages', null, ['section' => 'services', 'title' => 'Scholarships', 'parent_id' => $parent], null);
        $this->assertSame('citizens/scholarships', $content->find('pages', $child)['path']);

        $content->save('pages', $parent, ['section' => 'services', 'title' => 'Citizens', 'slug' => 'for-citizens'], null);
        $this->assertSame('for-citizens/scholarships', $content->find('pages', $child)['path']);
    }

    public function testAPageCannotBePlacedUnderItsOwnChild(): void
    {
        $content = service('content');
        $parent  = $content->save('pages', null, ['section' => 'services', 'title' => 'Parent'], null);
        $child   = $content->save('pages', null, ['section' => 'services', 'title' => 'Child', 'parent_id' => $parent], null);

        $this->expectException(InvalidArgumentException::class);
        $content->save('pages', $parent, ['section' => 'services', 'title' => 'Parent', 'parent_id' => $child], null);
    }

    public function testAParentPageMustBeInTheSameSection(): void
    {
        $content = service('content');
        $parent  = $content->save('pages', null, ['section' => 'services', 'title' => 'Parent'], null);

        $this->expectException(InvalidArgumentException::class);
        $content->save('pages', null, ['section' => 'about-mfaic', 'title' => 'Child', 'parent_id' => $parent], null);
    }

    public function testPublishedContentCannotBeDeleted(): void
    {
        $id = $this->content('articles', ['category' => 'news', 'title' => 'Keep me']);

        $this->expectException(InvalidArgumentException::class);
        service('content')->delete('articles', $id, null);
    }

    public function testTypingTheEngagementTagNameUsesTheSystemTag(): void
    {
        $this->seed(\App\Database\Seeds\StructureSeeder::class);
        $id = service('content')->save('articles', null, ['category' => 'news', 'title' => 'Visit', 'tags' => "minister's engagements, Trade"], null);

        $slugs = array_column($this->db->table('article_tag')->select('tags.slug')->join('tags', 'tags.id = article_tag.tag_id')->where('article_id', $id)->get()->getResultArray(), 'slug');
        sort($slugs);

        $this->assertSame(['minister-engagements', 'trade'], $slugs);
    }

    public function testSystemPagesKeepTheirPlaceInTheStructure(): void
    {
        $this->seed(\App\Database\Seeds\StructureSeeder::class);
        $page = $this->db->table('pages')->where('path', 'citizens/consular-services')->get()->getRowArray();

        service('content')->save('pages', (int) $page['id'], ['section' => 'about-mfaic', 'slug' => 'moved', 'parent_id' => null, 'title' => 'Consular Services', 'body' => 'Text'], null);

        $after = service('content')->find('pages', (int) $page['id']);
        $this->assertSame('services', $after['section']);
        $this->assertSame('citizens/consular-services', $after['path']);
        $this->assertSame('Text', $after['body']);
    }
}
