<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => $crumbs]) ?>
    <h1 class="page-title"><?= esc($heading) ?></h1>
    <div class="alert alert-success" role="status" data-testid="submission-received">
        <p class="mb-1"><?= esc($message) ?></p>
        <p class="mb-0"><?= esc(lang('Forms.referenceIs', [$reference])) ?></p>
    </div>
</div>
<?= $this->endSection() ?>
