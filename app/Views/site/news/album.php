<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($album['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.newsPress'), locale_url('news-press')],
        [lang('Public.gallery'), locale_url('news-press/gallery')],
        [$album['title']],
    ]]) ?>
    <h1 class="page-title"><?= esc($album['title']) ?></h1>
    <?php if (! empty($album['summary'])): ?><p class="lead"><?= esc($album['summary']) ?></p><?php endif ?>
    <?= $media === [] ? view('site/partials/empty') : view('site/partials/media', ['media' => $media]) ?>
</div>
<?= $this->endSection() ?>
