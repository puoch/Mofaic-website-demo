<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Public.socialMedia')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.newsPress'), locale_url('news-press')], [lang('Public.socialMedia')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Public.socialMedia')) ?></h1>
    <h2 class="section-heading"><?= esc(lang('Public.officialAccounts')) ?></h2>
    <?php if ($accounts === []): ?>
        <?= view('site/partials/empty') ?>
    <?php else: ?>
    <ul class="link-list" data-testid="social-accounts">
        <?php foreach ($accounts as $account): ?>
        <li><span class="text-muted"><?= esc($account['platform']) ?>:</span> <a href="<?= esc($account['url'], 'attr') ?>" rel="noopener" target="_blank"><?= esc($account['label']) ?> <span class="visually-hidden"><?= esc(lang('Public.opensExternal')) ?></span></a></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
    <p class="section-pending"><?= esc(lang('Public.socialEmbedNote')) ?></p>
</div>
<?= $this->endSection() ?>
