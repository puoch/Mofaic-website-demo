<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Public.countryDirectory')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.missions'), locale_url('missions')], [lang('Public.countryDirectory')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Public.countryDirectory')) ?></h1>
    <p><?= esc(lang('Public.countryDirectoryIntro')) ?></p>
    <?php if ($countries === []): ?>
        <?= view('site/partials/empty') ?>
    <?php else: ?>
    <ul class="country-list" data-testid="country-list">
        <?php foreach ($countries as $country): ?>
        <li><a href="<?= esc(locale_url('missions/countries/' . $country['iso_code']), 'attr') ?>"><?= esc($country['name']) ?></a></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
