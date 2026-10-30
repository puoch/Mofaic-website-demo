<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Public.tagged', [$tag['name']])) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.newsPress'), locale_url('news-press')], [$tag['name']]]]) ?>
    <h1 class="page-title"><?= esc(lang('Public.tagged', [$tag['name']])) ?></h1>
    <?= view('site/partials/article_cards', ['articles' => $articles]) ?>
</div>
<?= $this->endSection() ?>
