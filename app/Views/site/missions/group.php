<?php
$all     = config('ContentTypes')->missionCategories;
$heading = $group === 'abroad' ? lang('Site.nav.missionsAbroad') : lang('Site.nav.missionsInSouthSudan');
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.missions'), locale_url('missions')], [$heading]]]) ?>
    <h1 class="page-title"><?= esc($heading) ?></h1>
    <ul class="category-cards">
        <?php foreach ($categories as $key): ?>
        <li><a href="<?= esc(locale_url('missions/' . $all[$key]['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $all[$key]['navKey'])) ?></a>
            <span class="text-muted d-block"><?= esc(lang('Public.missionCount', [$counts[$key]])) ?></span></li>
        <?php endforeach ?>
    </ul>
</div>
<?= $this->endSection() ?>
