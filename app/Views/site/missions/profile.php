<?php
use App\Libraries\MediaService;

$definition = config('ContentTypes')->missionCategories[$mission['category']] ?? null;
$group      = str_starts_with($mission['category'], 'abroad_') ? 'abroad' : 'in-south-sudan';
$groupLabel = $group === 'abroad' ? lang('Site.nav.missionsAbroad') : lang('Site.nav.missionsInSouthSudan');
$typeLabel  = $definition === null ? '' : lang('Site.nav.' . $definition['navKey']);
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($mission['name']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [
        [lang('Site.nav.missions'), locale_url('missions')],
        [$groupLabel, locale_url('missions/' . $group)],
        [$typeLabel, $definition === null ? null : locale_url('missions/' . $definition['path'])],
        [$mission['name']],
    ]]) ?>

    <article class="mission-profile" data-testid="mission-profile">
        <h1 class="page-title"><?= esc($mission['name']) ?></h1>
        <p class="text-muted"><?= esc($typeLabel) ?> · <?= esc($groupLabel) ?></p>

        <?php if ($media !== []): ?>
        <img class="mission-image" src="<?= esc(MediaService::url($media[0]), 'attr') ?>" alt="<?= esc((string) $media[0]['alt_text'], 'attr') ?>">
        <?php endif ?>

        <div class="mission-facts">
            <section aria-labelledby="mission-location">
                <h2 id="mission-location" class="h5"><?= esc(lang('Public.location')) ?></h2>
                <p><?= esc(implode(', ', array_filter([$mission['city'], $mission['location_country']]))) ?></p>
                <?php if (! empty($mission['address'])): ?><address><?= nl2br(esc($mission['address'])) ?></address><?php endif ?>
                <?php if (! empty($mission['represents_country'])): ?>
                <p><?= esc(lang('Public.represents')) ?>: <?= esc($mission['represents_country']) ?></p>
                <?php endif ?>
            </section>

            <section aria-labelledby="mission-contacts">
                <h2 id="mission-contacts" class="h5"><?= esc(lang('Public.contacts')) ?></h2>
                <dl class="contact-list">
                    <?php if (! empty($mission['phone'])): ?>
                    <dt><?= esc(lang('Public.telephone')) ?></dt><dd><a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $mission['phone']), 'attr') ?>"><?= esc($mission['phone']) ?></a></dd>
                    <?php endif ?>
                    <?php if (! empty($mission['email'])): ?>
                    <dt><?= esc(lang('Public.email')) ?></dt><dd><a href="mailto:<?= esc($mission['email'], 'attr') ?>"><?= esc($mission['email']) ?></a></dd>
                    <?php endif ?>
                    <?php if (! empty($mission['website'])): ?>
                    <dt><?= esc(lang('Public.website')) ?></dt><dd><a href="<?= esc($mission['website'], 'attr') ?>" rel="noopener" target="_blank"><?= esc($mission['website']) ?></a></dd>
                    <?php endif ?>
                </dl>
            </section>

            <?php if (! empty($mission['head_of_mission_name'])): ?>
            <section aria-labelledby="mission-head">
                <h2 id="mission-head" class="h5"><?= esc(lang('Public.headOfMission')) ?></h2>
                <p><?= esc($mission['head_of_mission_name']) ?><?php if (! empty($mission['head_of_mission_title'])): ?><br><span class="text-muted"><?= esc($mission['head_of_mission_title']) ?></span><?php endif ?></p>
            </section>
            <?php endif ?>
        </div>

        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>

        <?php if ($coverage !== []): ?>
        <section aria-labelledby="mission-coverage" data-testid="countries-served">
            <h2 id="mission-coverage" class="h5"><?= esc(lang('Public.countriesServed')) ?></h2>
            <ul class="link-list">
                <?php foreach ($coverage as $country): ?>
                <li><a href="<?= esc(locale_url('missions/countries/' . $country['iso_code']), 'attr') ?>"><?= esc($country['name']) ?></a>
                    <span class="text-muted">(<?= esc($country['coverage'] === 'non_resident' ? lang('Public.nonResidentNoPlace') : lang('Public.resident')) ?>)</span></li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endif ?>

        <?php if ($related !== []): ?>
        <section aria-labelledby="mission-related" data-testid="related-missions">
            <h2 id="mission-related" class="h5"><?= esc(lang('Public.relatedMissions')) ?></h2>
            <?= view('site/missions/_mission_list', ['missions' => $related]) ?>
        </section>
        <?php endif ?>

        <section aria-labelledby="mission-activities">
            <h2 id="mission-activities" class="h5"><?= esc(lang('Public.activities')) ?></h2>
            <?= view('site/partials/article_list', ['articles' => $activities]) ?>
        </section>
    </article>
</div>
<?= $this->endSection() ?>
