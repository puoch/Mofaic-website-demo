<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Public.archives')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Public.archives')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Public.archives')) ?></h1>
    <?php if ($items === []): ?>
        <?= view('site/partials/empty') ?>
    <?php else: ?>
    <ul class="article-list">
        <?php foreach ($items as $item): ?>
        <li class="article-item">
            <h2 class="article-title h5"><a href="<?= esc(locale_url('archives/' . $item['slug']), 'attr') ?>"><?= esc($item['title']) ?></a></h2>
            <p class="article-date"><?= esc(format_date($item['published_at'])) ?></p>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
