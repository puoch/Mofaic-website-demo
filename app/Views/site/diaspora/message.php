<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.stayInformed'), locale_url('diaspora/stay-informed')], [$heading]]]) ?>
    <h1 class="page-title"><?= esc($heading) ?></h1>
    <p role="status" data-testid="message"><?= esc($message) ?></p>
</div>
<?= $this->endSection() ?>
