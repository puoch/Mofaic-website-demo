<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($article['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.diaspora'), locale_url('diaspora')],
        [lang('Forms.stayInformed'), locale_url('diaspora/stay-informed')],
        [$article['title']],
    ]]) ?>
    <article class="content-page">
        <h1 class="page-title"><?= esc($article['title']) ?></h1>
        <p class="article-date"><time datetime="<?= esc(substr((string) $article['published_at'], 0, 10), 'attr') ?>"><?= esc(lang('Public.published', [format_date($article['published_at'])])) ?></time></p>
        <?php if (! empty($article['summary'])): ?><p class="lead"><?= esc($article['summary']) ?></p><?php endif ?>
        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>
        <?= view('site/partials/media', ['media' => $media]) ?>
    </article>
</div>
<?= $this->endSection() ?>
