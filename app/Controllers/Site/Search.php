<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Libraries\FullTextQuery;
use CodeIgniter\Database\BaseBuilder;

/**
 * Site search, opened from the search button in the main navigation.
 *
 * Looks through published content in the current language: news,
 * statements, mission activities and diaspora articles; About and Services
 * pages; and missions (name, city or host country). On MySQL, articles use
 * the full-text index (PD-15) like the News / Press lists; everything else,
 * and words the index skips, use a plain text match.
 */
class Search extends SiteController
{
    private const LIMIT = 20;

    public function index(): string
    {
        $q = $this->request->getGet('q');
        $q = is_string($q) ? mb_substr(trim($q), 0, 100) : '';

        $results = ['articles' => [], 'pages' => [], 'missions' => []];
        if ($q !== '') {
            $results = [
                'articles' => $this->articles($q),
                'pages'    => $this->pages($q),
                'missions' => $this->missions($q),
            ];
        }

        return view('site/search', [
            'q'       => $q,
            'results' => $results,
            'total'   => array_sum(array_map('count', $results)),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articles(string $q): array
    {
        $builder  = $this->published('articles');
        $db       = db_connect();
        $fullText = $db->DBDriver === 'MySQLi' ? FullTextQuery::booleanMode($q) : null;

        if ($fullText !== null) {
            $builder->where('MATCH (title, summary, body) AGAINST (' . $db->escape($fullText) . ' IN BOOLEAN MODE)', null, false);
        } else {
            $this->like($builder, $q, ['title', 'summary', 'body']);
        }

        return $builder->orderBy('published_at', 'DESC')->limit(self::LIMIT)->get()->getResultArray();
    }

    /**
     * Published pages whose parents are published too (so every link works).
     *
     * @return list<array<string, mixed>>
     */
    private function pages(string $q): array
    {
        $builder = $this->published('pages');
        $this->like($builder, $q, ['title', 'summary', 'body']);

        $pages = $builder->orderBy('section')->orderBy('title')->limit(self::LIMIT * 2)->get()->getResultArray();
        $pages = array_filter($pages, static fn (array $page): bool => Pages::ancestors($page) !== null);

        return array_slice(array_values($pages), 0, self::LIMIT);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function missions(string $q): array
    {
        $builder = db_connect()->table('missions')
            ->select('missions.*, location.name AS location_name')
            ->join('countries AS location', 'location.id = missions.location_country_id', 'left')
            ->where(['missions.status' => 'published', 'missions.locale' => $this->locale()]);
        $this->like($builder, $q, ['missions.name', 'missions.city', 'location.name']);

        return $builder->orderBy('missions.name')->limit(self::LIMIT)->get()->getResultArray();
    }

    /**
     * Every word must appear in at least one of the columns.
     *
     * @param list<string> $columns
     */
    private function like(BaseBuilder $builder, string $q, array $columns): void
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $q, $m);
        $words = array_slice(array_unique($m[0]), 0, 8);

        if ($words === []) {
            $builder->where('1 = 0', null, false);

            return;
        }

        foreach ($words as $word) {
            $builder->groupStart();
            foreach ($columns as $i => $column) {
                $i === 0 ? $builder->like($column, $word) : $builder->orLike($column, $word);
            }
            $builder->groupEnd();
        }
    }
}
