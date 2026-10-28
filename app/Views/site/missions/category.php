<?php
$definition = config('ContentTypes')->missionCategories[$category];
$heading    = lang('Site.nav.' . $definition['navKey']);
$groupLabel = $group === 'abroad' ? lang('Site.nav.missionsAbroad') : lang('Site.nav.missionsInSouthSudan');
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading . ' · ' . $groupLabel) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl pt-3">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.missions'), locale_url('missions')],
        [$groupLabel, locale_url('missions/' . $group)],
        [$heading],
    ]]) ?>
</div>
<div class="directory-band">
    <div class="container-xl">
        <h1 class="directory-title"><?= esc($heading) ?> <span class="visually-hidden">· <?= esc($groupLabel) ?></span></h1>
    </div>
</div>
<div class="container-xl directory-body">
    <?= view('site/missions/_mission_cards', ['missions' => $missions, 'accreditations' => $accreditations]) ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="<?= esc(base_url('assets/js/mission-filter.js'), 'attr') ?>" defer></script>
<?= $this->endSection() ?>
