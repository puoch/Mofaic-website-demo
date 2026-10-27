<?php

declare(strict_types=1);

namespace App\Controllers\Site;

/**
 * ARCHIVES: text with uploaded or externally linked media, keeping links to
 * related news and galleries. Classification and navigation placement are
 * deferred (brief sections 2 and 6.2); nothing is archived automatically.
 */
class Archives extends SiteController
{
    public function index(): string
    {
        return view('site/archives/index', [
            'items' => $this->published('archive_items')->orderBy('published_at', 'DESC')->get()->getResultArray(),
        ]);
    }

    public function show(string $slug): string
    {
        $item = $this->requireRow($this->published('archive_items')->where('slug', $slug)->get()->getRowArray());

        $article = $item['related_article_id'] === null ? null
            : $this->published('articles')->where('id', $item['related_article_id'])->get()->getRowArray();
        $gallery = $item['related_gallery_id'] === null ? null
            : $this->published('galleries')->where('id', $item['related_gallery_id'])->get()->getRowArray();

        return view('site/archives/show', [
            'item'     => $item,
            'bodyHtml' => service('markdown')->toHtml($item['body']),
            'media'    => service('media')->forOwner('archives', (int) $item['id']),
            'article'  => $article,
            'gallery'  => $gallery,
        ]);
    }
}
