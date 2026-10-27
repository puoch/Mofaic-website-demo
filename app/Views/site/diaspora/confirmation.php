<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.confirmationHeading')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.confirmationHeading')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Forms.confirmationHeading')) ?></h1>
    <div class="alert alert-success" role="status" data-testid="registration-confirmed">
        <p class="mb-1"><?= esc(lang('Forms.confirmationIntro')) ?></p>
        <p class="mb-0"><?= esc(lang('Forms.referenceIs', [$registration['reference']])) ?></p>
    </div>
    <?php if ($mission !== null): ?>
    <section class="mission-card" aria-labelledby="chosen-mission" data-testid="chosen-mission">
        <h2 id="chosen-mission" class="h4"><?= esc($mission['name']) ?></h2>
        <?php if (! empty($mission['address'])): ?><p class="mission-address"><?= nl2br(esc($mission['address'])) ?></p><?php endif ?>
        <ul class="list-unstyled">
            <?php if (! empty($mission['phone'])): ?><li><?= esc(lang('Forms.phone')) ?>: <a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', (string) $mission['phone']), 'attr') ?>"><?= esc($mission['phone']) ?></a></li><?php endif ?>
            <?php if (! empty($mission['email'])): ?><li><?= esc(lang('Forms.email')) ?>: <a href="mailto:<?= esc($mission['email'], 'attr') ?>"><?= esc($mission['email']) ?></a></li><?php endif ?>
        </ul>
        <p><a href="<?= esc(mission_url($mission), 'attr') ?>"><?= esc(lang('Forms.missionProfile')) ?></a></p>
    </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
