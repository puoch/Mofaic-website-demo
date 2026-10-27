<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.contributeHeading')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.contribute')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Forms.contributeHeading')) ?></h1>
    <?php if (! $formOpen): ?>
        <?= view('site/partials/form_closed') ?>
    <?php else: ?>
    <p><?= esc(lang('Forms.contributeAnonymous')) ?> <?= esc(lang('Forms.contributeNoPayments')) ?></p>
    <?= view('site/partials/form_errors', ['errors' => $errors]) ?>
    <form method="post" action="<?= esc(locale_url('diaspora/contribute'), 'attr') ?>" class="public-form" novalidate data-testid="contribution-form">
        <?= view('site/partials/form_guard', ['form' => 'contribution']) ?>
        <?php $common = ['values' => $values, 'errors' => $errors]; ?>
        <?= view('site/partials/field', $common + ['name' => 'category', 'label' => lang('Forms.category'), 'type' => 'select', 'required' => true, 'options' => $categories, 'placeholder' => lang('Forms.chooseCategory')]) ?>
        <?= view('site/partials/field', $common + ['name' => 'message', 'label' => lang('Forms.message'), 'type' => 'textarea', 'required' => true, 'help' => lang('Forms.freeTextWarning'), 'maxlength' => 5000]) ?>
        <?= view('site/partials/field', $common + ['name' => 'name', 'label' => lang('Forms.name'), 'type' => 'text', 'required' => false, 'autocomplete' => 'name', 'maxlength' => 190]) ?>
        <?= view('site/partials/field', $common + ['name' => 'email', 'label' => lang('Forms.email'), 'type' => 'email', 'required' => false, 'autocomplete' => 'email', 'maxlength' => 190]) ?>
        <?= view('site/partials/field', $common + ['name' => 'phone', 'label' => lang('Forms.phone'), 'type' => 'tel', 'required' => false, 'autocomplete' => 'tel', 'maxlength' => 50]) ?>
        <?= view('site/partials/privacy', ['privacyHtml' => $privacyHtml]) ?>
        <button type="submit" class="btn btn-primary"><?= esc(lang('Forms.send')) ?></button>
    </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
