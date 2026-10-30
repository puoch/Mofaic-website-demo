<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use Config\ContentTypes;

/**
 * Home page areas confirmed in the brief (section 2): featured news, the
 * Minister's recent engagements (tagged news), activities of diplomatic
 * missions, quick links, official emergency contacts and the country-based
 * Diplomatic Mission Finder. Laid out like the sponsor's reference
 * ministry home page: lead story beside the latest statements, then picture tiles,
 * news cards and mission activity cards.
 */
class Home extends SiteController
{
    public function index(): string
    {
        $types    = config(ContentTypes::class);
        $locale   = $this->locale();
        $articles = static fn () => db_connect()->table('articles')
            ->select('articles.*')
            ->where(['articles.status' => 'published', 'articles.locale' => $locale])
            ->orderBy('articles.published_at', 'DESC');

        // The lead story is the newest featured item, or else the newest
        // news item or statement, so the top of the page is never empty
        // once anything is published.
        $lead = $articles()->whereIn('category', ['news', 'statement'])->where('is_featured', 1)->limit(1)->get()->getRowArray()
            ?? $articles()->whereIn('category', ['news', 'statement'])->limit(1)->get()->getRowArray();
        $notLead = static fn ($query) => $lead === null ? $query : $query->where('articles.id !=', (int) $lead['id']);

        // The slideshow opens on the lead story, then the next newest news
        // items and statements (five slides at most).
        $slides = $lead === null ? [] : [$lead, ...$notLead($articles()->whereIn('category', ['news', 'statement']))->limit(4)->get()->getResultArray()];

        $statements = $notLead($articles()->where('category', 'statement'))->limit(4)->get()->getResultArray();
        // The newest three news items sit in the box beside the lead story;
        // current affairs shows the next three, or the same three while
        // there are no more, so it is never empty when news exists.
        $recentNews  = $notLead($articles()->where('category', 'news'))->limit(6)->get()->getResultArray();
        $latestNews  = array_slice($recentNews, 0, 3);
        $news        = array_slice($recentNews, 3) ?: $latestNews;

        $engagements = $articles()
            ->join('article_tag', 'article_tag.article_id = articles.id')
            ->join('tags', 'tags.id = article_tag.tag_id')
            ->where('tags.slug', $types->ministerEngagementTag)
            ->whereIn('articles.category', ['news', 'statement'])
            ->limit(4)
            ->get()
            ->getResultArray();

        $missionActivities = $articles()
            ->select('missions.name AS mission_name')
            ->join('missions', 'missions.id = articles.mission_id', 'left')
            ->where('articles.category', 'mission_activity')
            ->limit(3)
            ->get()
            ->getResultArray();

        $emergency = db_connect()->table('site_blocks')
            ->where(['block_key' => 'emergency_contacts', 'locale' => $locale])
            ->get()
            ->getRowArray();

        $withPhotos = array_merge($slides, $recentNews, $missionActivities);
        $images     = service('media')->firstImages('articles', array_map('intval', array_column($withPhotos, 'id')));

        return view('site/home', [
            'slides'            => $slides,
            'statements'        => $statements,
            'latestNews'        => $latestNews,
            'news'              => $news,
            'images'            => $images,
            'engagements'       => $engagements,
            'missionActivities' => $missionActivities,
            'quickLinks'        => db_connect()->table('quick_links')->where(['locale' => $locale, 'is_active' => 1])->orderBy('sort_order')->get()->getResultArray(),
            'socialAccounts'    => db_connect()->table('social_accounts')->where('is_active', 1)->orderBy('sort_order')->orderBy('label')->get()->getResultArray(),
            'emergencyHtml'     => service('markdown')->toHtml($emergency['body'] ?? null),
            'finderCountries'   => service('missionDirectory')->countriesWithMissions($locale),
        ]);
    }
}
