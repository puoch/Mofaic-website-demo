<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($country['name']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.missions'), locale_url('missions')],
        [lang('Public.countryDirectory'), locale_url('missions/countries')],
        [$country['name']],
    ]]) ?>
    <h1 class="page-title"><?= esc($country['name']) ?></h1>

    <section aria-labelledby="serving" data-testid="serving-missions">
        <h2 id="serving" class="section-heading"><?= esc(lang('Public.servingCountry', [$country['name']])) ?></h2>
        <?php if ($serving === []): ?>
        <p class="section-pending" data-testid="no-match"><?= esc(lang('Public.noMissionForCountry', [$country['name']])) ?></p>
        <?php else: ?>
        <?= view('site/missions/_mission_list', ['missions' => $serving, 'showCoverage' => true]) ?>
        <?php endif ?>
    </section>

    <?php if ($representation !== []): ?>
    <section aria-labelledby="representation" data-testid="representation">
        <h2 id="representation" class="section-heading"><?= esc(lang('Public.representationIn', [$country['name']])) ?></h2>
        <?= view('site/missions/_mission_list', ['missions' => $representation]) ?>
    </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
