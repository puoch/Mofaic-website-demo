<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.byCountry')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.byCountry')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Forms.byCountry')) ?></h1>
    <?php if ($pages === []): ?>
        <?= view('site/partials/empty') ?>
    <?php else: ?>
    <ul class="country-list" data-testid="diaspora-countries">
        <?php foreach ($pages as $page): ?>
        <li><a href="<?= esc(locale_url('diaspora/countries/' . $page['slug']), 'attr') ?>"><?= esc($page['country_name']) ?></a></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
