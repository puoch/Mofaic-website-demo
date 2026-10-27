<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[$heading]]]) ?>
    <h1 class="page-title"><?= esc($heading) ?></h1>
    <?php if ($contactHtml !== ''): ?>
        <div class="prose" data-testid="contact-information"><?= $contactHtml ?></div>
    <?php else: ?>
        <p class="section-pending"><?= esc(lang('Site.sectionPending')) ?></p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
