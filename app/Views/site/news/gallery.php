<?php
use App\Libraries\MediaService;

?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Public.gallery')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.newsPress'), locale_url('news-press')], [lang('Public.gallery')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Public.gallery')) ?></h1>
    <?php if ($albums === []): ?>
        <?= view('site/partials/empty') ?>
    <?php else: ?>
    <ul class="album-grid">
        <?php foreach ($albums as $album): ?>
        <li class="album-card">
            <?php if (isset($covers[(int) $album['id']])): ?>
            <img src="<?= esc(MediaService::url($covers[(int) $album['id']]), 'attr') ?>" alt="" loading="lazy">
            <?php endif ?>
            <h2 class="h5"><a href="<?= esc(locale_url('news-press/gallery/' . $album['slug']), 'attr') ?>"><?= esc($album['title']) ?></a></h2>
            <p class="article-date"><?= esc(format_date($album['published_at'])) ?></p>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
