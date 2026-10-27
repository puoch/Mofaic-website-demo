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

    <section class="form-section" aria-labelledby="inquiry-heading">
        <h2 id="inquiry-heading" class="section-heading"><?= esc(lang('Forms.inquiryHeading')) ?></h2>
        <?php if (! $formOpen): ?>
            <?= view('site/partials/form_closed') ?>
        <?php else: ?>
        <p><?= esc(lang('Forms.inquiryIntro')) ?></p>
        <?= view('site/partials/form_errors', ['errors' => $errors]) ?>
        <form method="post" action="<?= esc(locale_url('contacts'), 'attr') ?>" class="public-form" novalidate data-testid="inquiry-form">
            <?= view('site/partials/form_guard', ['form' => 'inquiry']) ?>
            <?php $common = ['values' => $values, 'errors' => $errors]; ?>
            <?= view('site/partials/field', $common + ['name' => 'name', 'label' => lang('Forms.name'), 'type' => 'text', 'required' => $required['name'], 'autocomplete' => 'name', 'maxlength' => 190]) ?>
            <?= view('site/partials/field', $common + ['name' => 'email', 'label' => lang('Forms.email'), 'type' => 'email', 'required' => $required['email'], 'autocomplete' => 'email', 'maxlength' => 190]) ?>
            <?= view('site/partials/field', $common + ['name' => 'phone', 'label' => lang('Forms.phone'), 'type' => 'tel', 'required' => $required['phone'], 'autocomplete' => 'tel', 'maxlength' => 50]) ?>
            <?= view('site/partials/field', $common + ['name' => 'subject', 'label' => lang('Forms.subject'), 'type' => 'text', 'required' => $required['subject'], 'maxlength' => 190]) ?>
            <?= view('site/partials/field', $common + ['name' => 'message', 'label' => lang('Forms.message'), 'type' => 'textarea', 'required' => $required['message'], 'help' => lang('Forms.freeTextWarning'), 'maxlength' => 5000]) ?>
            <?= view('site/partials/privacy', ['privacyHtml' => $privacyHtml]) ?>
            <button type="submit" class="btn btn-primary"><?= esc(lang('Forms.send')) ?></button>
        </form>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
