<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.chooseMissionHeading')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.register'), locale_url('diaspora/register')], [lang('Forms.chooseMissionHeading')]]]) ?>
    <?php $countryName = (string) ($country['name'] ?? ''); ?>
    <?php if ($missions === []): ?>
    <h1 class="page-title"><?= esc(lang('Forms.noMissionHeading', [$countryName])) ?></h1>
    <div class="alert alert-info" role="status" data-testid="no-mission">
        <p class="mb-1"><?= esc(lang('Forms.noMissionIntro')) ?></p>
        <p class="mb-0"><?= esc(lang('Forms.referenceIs', [$registration['reference']])) ?></p>
    </div>
    <?php if ($hqHtml !== ''): ?><div class="prose" data-testid="headquarters-contact"><?= $hqHtml ?></div><?php endif ?>
    <?php else: ?>
    <h1 class="page-title"><?= esc(lang('Forms.chooseMissionHeading')) ?></h1>
    <p><?= esc(lang('Forms.chooseMissionIntro', [$countryName])) ?></p>
    <p><?= esc(lang('Forms.referenceIs', [$registration['reference']])) ?></p>
    <ul class="mission-list" data-testid="serving-missions">
        <?php foreach ($missions as $mission): ?>
        <li class="mission-item">
            <h2 class="h5 mb-1"><?= esc($mission['name']) ?></h2>
            <p class="text-muted mb-2"><?= esc(implode(', ', array_filter([$mission['city'] ?? null, $mission['location_country'] ?? null]))) ?>
                <?php if (($mission['coverage'] ?? '') === 'non_resident'): ?> · <?= esc(lang('Forms.nonResident')) ?><?php endif ?></p>
            <form method="post" action="<?= esc(locale_url('diaspora/register/missions'), 'attr') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="mission_id" value="<?= (int) $mission['id'] ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm"><?= esc(lang('Forms.chooseMission')) ?><span class="visually-hidden">: <?= esc($mission['name']) ?></span></button>
            </form>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
