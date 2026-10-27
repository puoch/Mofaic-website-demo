<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($item['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Public.archives'), locale_url('archives')], [$item['title']]]]) ?>
    <article class="content-page">
        <h1 class="page-title"><?= esc($item['title']) ?></h1>
        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>
        <?= view('site/partials/media', ['media' => $media]) ?>
        <?php if ($article !== null || $gallery !== null): ?>
        <ul class="link-list">
            <?php if ($article !== null): ?>
            <li><?= esc(lang('Public.relatedArticle')) ?>: <a href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></li>
            <?php endif ?>
            <?php if ($gallery !== null): ?>
            <li><?= esc(lang('Public.relatedGallery')) ?>: <a href="<?= esc(locale_url('news-press/gallery/' . $gallery['slug']), 'attr') ?>"><?= esc($gallery['title']) ?></a></li>
            <?php endif ?>
        </ul>
        <?php endif ?>
    </article>
</div>
<?= $this->endSection() ?>
