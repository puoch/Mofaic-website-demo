<?php
/**
 * @var list<array<string, mixed>> $articles
 */
use App\Libraries\MediaService;

// The first photo of each article is shown small next to its title.
$photos = $articles === [] ? [] : service('media')->firstImages('articles', array_map('intval', array_column($articles, 'id')));
?>
<?php if ($articles === []): ?>
    <?= view('site/partials/empty') ?>
<?php else: ?>
<ul class="article-list">
    <?php foreach ($articles as $article): ?>
    <li class="article-item<?= isset($photos[(int) $article['id']]) ? ' has-photo' : '' ?>">
        <?php if (isset($photos[(int) $article['id']])): ?>
        <img class="article-photo" src="<?= esc(MediaService::url($photos[(int) $article['id']]), 'attr') ?>" alt="" loading="lazy">
        <?php endif ?>
        <h3 class="article-title"><a href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></h3>
        <p class="article-date"><time datetime="<?= esc(substr((string) $article['published_at'], 0, 10), 'attr') ?>"><?= esc(format_date($article['published_at'])) ?></time></p>
        <?php if (! empty($article['summary'])): ?>
        <p class="article-summary"><?= esc($article['summary']) ?></p>
        <?php endif ?>
    </li>
    <?php endforeach ?>
</ul>
<?php endif ?>
