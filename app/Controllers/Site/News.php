<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use CodeIgniter\I18n\Time;
use Exception;

/**
 * NEWS / PRESS: News, Statements, Social Media and Gallery (brief section 2),
 * plus mission activities and tag pages.
 *
 * News and statements are listed newest first, with keyword search and
 * date filters. Exact filter definitions are still to be confirmed.
 */
class News extends SiteController
{
    private const PER_PAGE = 10;

    /**
     * URL segment => [category, language key]
     */
    private const LISTS = [
        'news'               => ['news', 'Public.news'],
        'statements'         => ['statement', 'Public.statements'],
        'mission-activities' => ['mission_activity', 'Public.missionActivities'],
    ];

    public function index(): string
    {
        return view('site/news/index', [
            'latest' => $this->published('articles')->whereIn('category', ['news', 'statement'])->orderBy('published_at', 'DESC')->limit(5)->get()->getResultArray(),
        ]);
    }

    public function list(string $segment): string
    {
        [$category, $headingKey] = self::LISTS[$segment] ?? $this->notFound();

        $filters = $this->filters();
        $builder = $this->published('articles')->where('category', $category);
        $this->applyFilters($builder, $filters);

        $total    = (clone $builder)->countAllResults();
        $page     = max(1, (int) $this->request->getGet('page'));
        $articles = $builder->orderBy('published_at', 'DESC')->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)->get()->getResultArray();

        return view('site/news/list', [
            'heading'  => lang($headingKey),
            'segment'  => $segment,
            'articles' => $articles,
            'filters'  => $filters,
            'total'    => $total,
            'page'     => $page,
            'pages'    => (int) ceil($total / self::PER_PAGE),
        ]);
    }

    public function show(string $segment, string $slug): string
    {
        [$category, $headingKey] = self::LISTS[$segment] ?? $this->notFound();

        $article = $this->requireRow($this->published('articles')->where(['category' => $category, 'slug' => $slug])->get()->getRowArray());

        $tags = db_connect()->table('article_tag')
            ->select('tags.slug, tags.name')
            ->join('tags', 'tags.id = article_tag.tag_id')
            ->where('article_tag.article_id', $article['id'])
            ->orderBy('tags.name')
            ->get()
            ->getResultArray();

        $mission = null;
        if ($article['mission_id'] !== null) {
            $mission = db_connect()->table('missions')->where(['id' => $article['mission_id'], 'status' => 'published'])->get()->getRowArray();
        }

        return view('site/news/show', [
            'heading'  => lang($headingKey),
            'segment'  => $segment,
            'article'  => $article,
            'bodyHtml' => service('markdown')->toHtml($article['body']),
            'media'    => service('media')->forOwner('articles', (int) $article['id']),
            'tags'     => $tags,
            'mission'  => $mission,
        ]);
    }

    public function tag(string $slug): string
    {
        $tag = $this->requireRow(db_connect()->table('tags')->where('slug', $slug)->get()->getRowArray());

        $articles = $this->published('articles')
            ->select('articles.*')
            ->join('article_tag', 'article_tag.article_id = articles.id')
            ->where('article_tag.tag_id', $tag['id'])
            ->whereIn('articles.category', ['news', 'statement', 'mission_activity'])
            ->orderBy('published_at', 'DESC')
            ->limit(50)
            ->get()
            ->getResultArray();

        return view('site/news/tag', ['tag' => $tag, 'articles' => $articles]);
    }

    public function gallery(): string
    {
        $albums = $this->published('galleries')->orderBy('published_at', 'DESC')->get()->getResultArray();

        return view('site/news/gallery', [
            'albums' => $albums,
            'covers' => service('media')->firstImages('galleries', array_map('intval', array_column($albums, 'id'))),
        ]);
    }

    public function album(string $slug): string
    {
        $album = $this->requireRow($this->published('galleries')->where('slug', $slug)->get()->getRowArray());

        return view('site/news/album', [
            'album' => $album,
            'media' => service('media')->forOwner('galleries', (int) $album['id']),
        ]);
    }

    public function social(): string
    {
        return view('site/news/social', [
            'accounts' => db_connect()->table('social_accounts')->where('is_active', 1)->orderBy('sort_order')->orderBy('label')->get()->getResultArray(),
        ]);
    }


    /**
     * @return array{q: string, from: string, to: string}
     */
    private function filters(): array
    {
        $date = static function (mixed $value): string {
            if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
                return '';
            }

            try {
                return Time::createFromFormat('Y-m-d', $value, 'UTC')->format('Y-m-d');
            } catch (Exception) {
                return '';
            }
        };

        $q = $this->request->getGet('q');

        return [
            'q'    => is_string($q) ? mb_substr(trim($q), 0, 100) : '',
            'from' => $date($this->request->getGet('from')),
            'to'   => $date($this->request->getGet('to')),
        ];
    }

    /**
     * @param array{q: string, from: string, to: string} $filters
     */
    private function applyFilters(\CodeIgniter\Database\BaseBuilder $builder, array $filters): void
    {
        if ($filters['q'] !== '') {
            $builder->groupStart()
                ->like('title', $filters['q'])
                ->orLike('summary', $filters['q'])
                ->orLike('body', $filters['q'])
                ->groupEnd();
        }

        if ($filters['from'] !== '') {
            $builder->where('published_at >=', $filters['from'] . ' 00:00:00');
        }

        if ($filters['to'] !== '') {
            $builder->where('published_at <=', $filters['to'] . ' 23:59:59');
        }
    }
}
