<?php
/**
 * Articles as cards like the home page (large photo, date, headline, three
 * across on wide screens). Used by the news, statements and mission
 * activity listings.
 *
 * @var list<array<string, mixed>> $articles
 */
use App\Libraries\MediaService;

$photos = $articles === [] ? [] : service('media')->firstImages('articles', array_map('intval', array_column($articles, 'id')));
?>
<?php if ($articles === []): ?>
    <?= view('site/partials/empty') ?>
<?php else: ?>
<ul class="home-cards">
    <?php foreach ($articles as $article): ?>
    <li class="home-card">
        <?php if (isset($photos[(int) $article['id']])): ?>
        <img class="home-card-photo" src="<?= esc(MediaService::url($photos[(int) $article['id']]), 'attr') ?>" alt="" loading="lazy">
        <?php else: ?>
        <div class="home-card-photo home-photo-none" aria-hidden="true"></div>
        <?php endif ?>
        <time class="home-date" datetime="<?= esc(substr((string) $article['published_at'], 0, 10), 'attr') ?>"><?= esc(format_date($article['published_at'])) ?></time>
        <h2><a class="stretched-link" href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></h2>
    </li>
    <?php endforeach ?>
</ul>
<?php endif ?>
