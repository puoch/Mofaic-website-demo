<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.unsubscribeHeading')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.unsubscribeHeading')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Forms.unsubscribeHeading')) ?></h1>
    <?php if (! $valid): ?>
    <p data-testid="message"><?= esc(lang('Forms.unsubscribeFailed')) ?></p>
    <?php else: ?>
    <form method="post" action="<?= esc(locale_url('diaspora/newsletter/unsubscribe/' . $id . '/' . $signature), 'attr') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-primary"><?= esc(lang('Forms.unsubscribeButton')) ?></button>
    </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
