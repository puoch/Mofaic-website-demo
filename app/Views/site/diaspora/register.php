<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.registerHeading')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.register')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Forms.registerHeading')) ?></h1>
    <?php if (! $formOpen): ?>
        <?= view('site/partials/form_closed') ?>
    <?php else: ?>
    <div class="prose" data-testid="voluntary-statement"><?= $statementHtml ?></div>
    <?= view('site/partials/form_errors', ['errors' => $errors]) ?>
    <form method="post" action="<?= esc(locale_url('diaspora/register'), 'attr') ?>" class="public-form" novalidate data-testid="registration-form">
        <?= view('site/partials/form_guard', ['form' => 'registration']) ?>
        <?php $common = ['values' => $values, 'errors' => $errors]; ?>
        <?= view('site/partials/field', $common + ['name' => 'name', 'label' => lang('Forms.fullName'), 'type' => 'text', 'required' => $nameRequired, 'autocomplete' => 'name', 'maxlength' => 190]) ?>
        <?= view('site/partials/field', $common + ['name' => 'address', 'label' => lang('Forms.address'), 'type' => 'textarea', 'required' => false, 'autocomplete' => 'street-address', 'maxlength' => 500]) ?>
        <?= view('site/partials/field', $common + ['name' => 'city', 'label' => lang('Forms.city'), 'type' => 'text', 'required' => true, 'autocomplete' => 'address-level2', 'maxlength' => 190]) ?>
        <?= view('site/partials/field', $common + ['name' => 'locality', 'label' => lang('Forms.locality'), 'type' => 'text', 'required' => false, 'maxlength' => 190]) ?>
        <?= view('site/partials/field', $common + ['name' => 'country_id', 'label' => lang('Forms.countryOfResidence'), 'type' => 'select', 'required' => true, 'options' => $countries, 'placeholder' => lang('Forms.chooseCountry')]) ?>
        <fieldset class="mb-3">
            <legend class="form-label fs-6"><?= esc(lang('Forms.emailOrPhone')) ?></legend>
            <?= view('site/partials/field', $common + ['name' => 'email', 'label' => lang('Forms.email'), 'type' => 'email', 'required' => false, 'autocomplete' => 'email', 'maxlength' => 190]) ?>
            <?= view('site/partials/field', $common + ['name' => 'phone', 'label' => lang('Forms.phone'), 'type' => 'tel', 'required' => false, 'autocomplete' => 'tel', 'maxlength' => 50]) ?>
        </fieldset>
        <?= view('site/partials/field', $common + ['name' => 'inquiry_nature', 'label' => lang('Forms.inquiryNature'), 'type' => 'textarea', 'required' => false, 'help' => lang('Forms.freeTextWarning'), 'maxlength' => 2000]) ?>
        <?= view('site/partials/field', $common + ['name' => 'consular_help', 'label' => lang('Forms.consularHelp'), 'type' => 'textarea', 'required' => false, 'help' => lang('Forms.freeTextWarning'), 'maxlength' => 2000]) ?>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="field-newsletter" name="newsletter" value="1"<?= $newsletter ? ' checked' : '' ?>>
            <label class="form-check-label" for="field-newsletter"><?= esc(lang('Forms.newsletterOptIn')) ?></label>
        </div>
        <?= view('site/partials/privacy', ['privacyHtml' => $privacyHtml]) ?>
        <button type="submit" class="btn btn-primary"><?= esc(lang('Forms.send')) ?></button>
    </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
