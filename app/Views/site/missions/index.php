<?php
/**
 * MISSIONS landing page: two full-width buttons styled like the Services
 * page, one for each half of the confirmed hierarchy. The Mission Finder
 * stays on the home page and the country directory at missions/countries.
 */
$buttons = [
    locale_url('missions/abroad')         => lang('Public.missionsAbroadButton'),
    locale_url('missions/in-south-sudan') => lang('Site.nav.missionsInSouthSudan'),
];
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.nav.missions')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.missions')]]]) ?>
    <h1 class="page-title page-title-center"><?= esc(lang('Site.nav.missions')) ?></h1>

    <?= view('site/partials/service_choices', ['choices' => $buttons, 'label' => lang('Public.missionChoices')]) ?>
</div>
<?= $this->endSection() ?>
