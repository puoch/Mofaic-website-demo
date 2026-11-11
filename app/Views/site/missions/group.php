<?php
/**
 * One half of the MISSIONS hierarchy, shown as full-width buttons styled like
 * the Services page (one button per mission type).
 *
 * @var string       $group      abroad | in-south-sudan
 * @var list<string> $categories mission category keys, in display order
 */
$all     = config('ContentTypes')->missionCategories;
$heading = $group === 'abroad' ? lang('Public.missionsAbroadButton') : lang('Site.nav.missionsInSouthSudan');
// Diany's wording for the buttons; the menu keeps its own labels.
$labels  = ['abroad_consulate' => lang('Public.abroadConsulateButton')];
$buttons = [];
foreach ($categories as $key) {
    $buttons[locale_url('missions/' . $all[$key]['path'])] = $labels[$key] ?? lang('Site.nav.' . $all[$key]['navKey']);
}
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.missions'), locale_url('missions')], [$heading]]]) ?>
    <h1 class="page-title page-title-center"><?= esc($heading) ?></h1>

    <?= view('site/partials/service_choices', ['choices' => $buttons, 'label' => $heading]) ?>
</div>
<?= $this->endSection() ?>
