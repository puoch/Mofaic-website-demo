<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use Config\ContentTypes;

/**
 * Home page areas confirmed in the brief (section 2): featured news, the
 * Minister's recent engagements (tagged news), activities of diplomatic
 * missions, quick links, official emergency contacts and the country-based
 * Diplomatic Mission Finder.
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

        $featured = $articles()->whereIn('category', ['news', 'statement'])->where('is_featured', 1)->limit(3)->get()->getResultArray();

        $engagements = $articles()
            ->join('article_tag', 'article_tag.article_id = articles.id')
            ->join('tags', 'tags.id = article_tag.tag_id')
            ->where('tags.slug', $types->ministerEngagementTag)
            ->whereIn('articles.category', ['news', 'statement'])
            ->limit(4)
            ->get()
            ->getResultArray();

        $missionActivities = $articles()->where('category', 'mission_activity')->limit(4)->get()->getResultArray();

        $emergency = db_connect()->table('site_blocks')
            ->where(['block_key' => 'emergency_contacts', 'locale' => $locale])
            ->get()
            ->getRowArray();

        $images = service('media')->firstImages('articles', array_map('intval', array_column($featured, 'id')));

        return view('site/home', [
            'featured'          => $featured,
            'featuredImages'    => $images,
            'engagements'       => $engagements,
            'missionActivities' => $missionActivities,
            'quickLinks'        => db_connect()->table('quick_links')->where(['locale' => $locale, 'is_active' => 1])->orderBy('sort_order')->get()->getResultArray(),
            'emergencyHtml'     => service('markdown')->toHtml($emergency['body'] ?? null),
            'finderCountries'   => service('missionDirectory')->countriesWithMissions($locale),
        ]);
    }
}
