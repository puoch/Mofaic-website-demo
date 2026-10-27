<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.nav.newsPress')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.newsPress')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Site.nav.newsPress')) ?></h1>

    <ul class="category-cards" data-testid="news-categories">
        <li><a href="<?= esc(locale_url('news-press/news'), 'attr') ?>"><?= esc(lang('Public.news')) ?></a></li>
        <li><a href="<?= esc(locale_url('news-press/statements'), 'attr') ?>"><?= esc(lang('Public.statements')) ?></a></li>
        <li><a href="<?= esc(locale_url('news-press/social-media'), 'attr') ?>"><?= esc(lang('Public.socialMedia')) ?></a></li>
        <li><a href="<?= esc(locale_url('news-press/gallery'), 'attr') ?>"><?= esc(lang('Public.gallery')) ?></a></li>
    </ul>

    <h2 class="section-heading"><?= esc(lang('Public.latest')) ?></h2>
    <?= view('site/partials/article_list', ['articles' => $latest]) ?>
</div>
<?= $this->endSection() ?>
