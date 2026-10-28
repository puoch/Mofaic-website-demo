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
        <?php
        $flag    = flag_url(mission_flag_iso($mission));
        $pending = '<span class="text-muted">' . esc(lang('Public.toBeConfirmed')) . '</span>';
        $place   = implode(', ', array_filter([$mission['city'], $mission['location_country']]));
        ?>
        <div class="row gy-4 gx-lg-5">
        <div class="<?= $media === [] ? 'col-12' : 'col-lg-7' ?>">
        <div class="mission-heading">
            <?php if ($flag !== null): ?>
            <img class="flag" src="<?= esc($flag, 'attr') ?>" alt="" width="96" height="72">
            <?php endif ?>
            <div>
                <h1 class="page-title"><?= esc($mission['name']) ?></h1>
                <p class="text-muted mb-0"><?= esc(implode(' · ', array_filter([$place, $typeLabel]))) ?></p>
            </div>
        </div>

        <?php /* Every row is shown, so a missing detail reads "To be confirmed" instead of disappearing. */ ?>
        <table class="table mission-details" data-testid="mission-details">
            <caption class="visually-hidden"><?= esc(lang('Public.missionDetails')) ?></caption>
            <tbody>
                <tr>
                    <th scope="row"><?= esc(lang('Public.headOfMission')) ?></th>
                    <td><?php if (! empty($mission['head_of_mission_name'])): ?><?= esc($mission['head_of_mission_name']) ?><?php if (! empty($mission['head_of_mission_title'])): ?><br><span class="text-muted"><?= esc($mission['head_of_mission_title']) ?></span><?php endif ?><?php else: ?><?= $pending ?><?php endif ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= esc(lang('Public.location')) ?></th>
                    <td><?= $place !== '' ? esc($place) : $pending ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= esc(lang('Public.address')) ?></th>
                    <td><?= ! empty($mission['address']) ? '<address class="mb-0">' . nl2br(esc($mission['address'])) . '</address>' : $pending ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= esc(lang('Public.telephone')) ?></th>
                    <td><?= ! empty($mission['phone']) ? '<a href="tel:' . esc(preg_replace('/[^0-9+]/', '', $mission['phone']), 'attr') . '">' . esc($mission['phone']) . '</a>' : $pending ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= esc(lang('Public.email')) ?></th>
                    <td><?= ! empty($mission['email']) ? '<a href="mailto:' . esc($mission['email'], 'attr') . '">' . esc($mission['email']) . '</a>' : $pending ?></td>
                </tr>
                <tr>
                    <th scope="row"><?= esc(lang('Public.website')) ?></th>
                    <td><?= ! empty($mission['website']) ? '<a href="' . esc($mission['website'], 'attr') . '" rel="noopener" target="_blank">' . esc($mission['website']) . ' <span class="visually-hidden">' . esc(lang('Public.opensExternal')) . '</span></a>' : $pending ?></td>
                </tr>
                <?php if (! empty($mission['represents_country'])): ?>
                <tr>
                    <th scope="row"><?= esc(lang('Public.represents')) ?></th>
                    <td><?= esc($mission['represents_country']) ?></td>
                </tr>
                <?php endif ?>
            </tbody>
        </table>

        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>

        <?php if ($coverage !== []): ?>
        <section aria-labelledby="mission-coverage" data-testid="countries-served">
            <h2 id="mission-coverage" class="h5"><?= esc(lang('Public.countriesServed')) ?></h2>
            <ul class="link-list">
                <?php foreach ($coverage as $country): ?>
                <li><?php if (($countryFlag = flag_url($country['iso_code'])) !== null): ?><img class="flag flag-sm" src="<?= esc($countryFlag, 'attr') ?>" alt="" width="24" height="18" loading="lazy"> <?php endif ?><a href="<?= esc(locale_url('missions/countries/' . $country['iso_code']), 'attr') ?>"><?= esc($country['name']) ?></a>
                    <span class="text-muted">(<?= esc($country['coverage'] === 'non_resident' ? lang('Public.nonResidentNoPlace') : lang('Public.resident')) ?>)</span></li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endif ?>

        <?php if ($consulates !== null): ?>
        <section aria-labelledby="mission-consulates" data-testid="consulates">
            <h2 id="mission-consulates" class="h5"><?= esc(lang('Public.consulatesIn', [$mission['location_country']])) ?></h2>
            <?php if ($consulates === []): ?>
            <p><?= esc(lang('Public.noConsulateIn', [$mission['location_country']])) ?></p>
            <?php else: ?>
            <?= view('site/missions/_mission_list', ['missions' => $consulates]) ?>
            <?php endif ?>
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
        </div>

        <?php if ($media !== []): ?>
        <?php /* The first image is the main photo of the mission; the rest are shown smaller below it. Each opens full size. */ ?>
        <aside class="col-lg-5" aria-labelledby="mission-photos-heading" data-testid="mission-photos">
            <h2 id="mission-photos-heading" class="visually-hidden"><?= esc(lang('Public.images')) ?></h2>
            <ul class="mission-gallery">
                <?php foreach ($media as $i => $image): ?>
                <li class="<?= $i === 0 ? 'mission-gallery-main' : 'mission-gallery-thumb' ?>">
                    <a href="<?= esc(MediaService::url($image), 'attr') ?>">
                        <img src="<?= esc(MediaService::url($image), 'attr') ?>" alt="<?= esc((string) $image['alt_text'], 'attr') ?>"<?= $i === 0 ? '' : ' loading="lazy"' ?>>
                    </a>
                </li>
                <?php endforeach ?>
            </ul>
        </aside>
        <?php endif ?>
        </div>
    </article>
</div>
<?= $this->endSection() ?>
