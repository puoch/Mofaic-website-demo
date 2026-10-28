<?php
$definition = config('ContentTypes')->missionCategories[$category];
$heading    = lang('Site.nav.' . $definition['navKey']);
$groupLabel = $group === 'abroad' ? lang('Site.nav.missionsAbroad') : lang('Site.nav.missionsInSouthSudan');
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading . ' · ' . $groupLabel) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.missions'), locale_url('missions')],
        [$groupLabel, locale_url('missions/' . $group)],
        [$heading],
    ]]) ?>
    <h1 class="page-title"><?= esc($heading) ?> <small class="text-muted">· <?= esc($groupLabel) ?></small></h1>
    <?= view('site/missions/_mission_cards', ['missions' => $missions, 'accreditations' => $accreditations]) ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
    <script src="<?= esc(base_url('assets/js/mission-filter.js'), 'attr') ?>" defer></script>
<?= $this->endSection() ?>
