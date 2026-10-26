<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <h1 class="page-title"><?= esc($heading) ?></h1>
    <p class="section-pending"><?= esc(lang('Site.sectionPending')) ?></p>
</div>
<?= $this->endSection() ?>
