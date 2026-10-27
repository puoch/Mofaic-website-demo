<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($article['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.newsPress'), locale_url('news-press')],
        [$heading, locale_url('news-press/' . $segment)],
        [$article['title']],
    ]]) ?>
    <article class="content-page">
        <h1 class="page-title"><?= esc($article['title']) ?></h1>
        <p class="article-date"><time datetime="<?= esc(substr((string) $article['published_at'], 0, 10), 'attr') ?>"><?= esc(lang('Public.published', [format_date($article['published_at'])])) ?></time></p>
        <?php if ($mission !== null): ?>
        <p><?= esc(lang('Public.fromMission')) ?> <a href="<?= esc(mission_url($mission), 'attr') ?>"><?= esc($mission['name']) ?></a></p>
        <?php endif ?>
        <?php if (! empty($article['summary'])): ?><p class="lead"><?= esc($article['summary']) ?></p><?php endif ?>
        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>

        <?= view('site/partials/media', ['media' => $media]) ?>

        <?php if ($tags !== []): ?>
        <p class="tag-list"><span class="visually-hidden"><?= esc(lang('Public.tags')) ?>:</span>
            <?php foreach ($tags as $tag): ?>
            <a class="badge text-bg-light" href="<?= esc(locale_url('news-press/tags/' . $tag['slug']), 'attr') ?>"><?= esc($tag['name']) ?></a>
            <?php endforeach ?>
        </p>
        <?php endif ?>
    </article>
</div>
<?= $this->endSection() ?>
