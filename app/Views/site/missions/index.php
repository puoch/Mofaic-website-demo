<?php
$categories = config('ContentTypes')->missionCategories;
$groupLabels = ['abroad' => lang('Site.nav.missionsAbroad'), 'in-south-sudan' => lang('Site.nav.missionsInSouthSudan')];
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.nav.missions')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.missions')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Site.nav.missions')) ?></h1>

    <section class="home-finder mb-4" aria-labelledby="missions-finder" data-testid="mission-finder">
        <h2 id="missions-finder" class="section-heading"><?= esc(lang('Public.missionFinder')) ?></h2>
        <?= view('site/partials/finder', ['countries' => $countries]) ?>
        <p class="mt-2"><a href="<?= esc(locale_url('missions/countries'), 'attr') ?>"><?= esc(lang('Public.countryDirectory')) ?></a></p>
    </section>

    <div class="mission-groups" data-testid="mission-hierarchy">
        <?php foreach ($groups as $group => $keys): ?>
        <section aria-labelledby="group-<?= esc($group, 'attr') ?>">
            <h2 id="group-<?= esc($group, 'attr') ?>" class="section-heading"><a href="<?= esc(locale_url('missions/' . $group), 'attr') ?>"><?= esc($groupLabels[$group]) ?></a></h2>
            <ul class="link-list">
                <?php foreach ($keys as $key): ?>
                <li><a href="<?= esc(locale_url('missions/' . $categories[$key]['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $categories[$key]['navKey'])) ?></a>
                    <span class="text-muted">(<?= esc(lang('Public.missionCount', [$counts[$key]])) ?>)</span></li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
