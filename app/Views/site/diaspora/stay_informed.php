<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Forms.stayInformed')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.stayInformed')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Forms.stayInformed')) ?></h1>

    <nav aria-label="<?= esc(lang('Forms.stayInformed'), 'attr') ?>">
        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link<?= $filter === '' ? ' active' : '' ?>"<?= $filter === '' ? ' aria-current="page"' : '' ?> href="<?= esc(locale_url('diaspora/stay-informed'), 'attr') ?>"><?= esc(lang('Forms.allUpdates')) ?></a></li>
            <?php foreach ($filters as $key => $label): ?>
            <li class="nav-item"><a class="nav-link<?= $filter === $key ? ' active' : '' ?>"<?= $filter === $key ? ' aria-current="page"' : '' ?> href="<?= esc(locale_url('diaspora/stay-informed') . '?type=' . $key, 'attr') ?>"><?= esc($label) ?></a></li>
            <?php endforeach ?>
        </ul>
    </nav>
    <?= view('site/partials/article_list', ['articles' => $articles]) ?>

    <section class="form-section" aria-labelledby="subscribe-heading">
        <h2 id="subscribe-heading" class="section-heading"><?= esc(lang('Forms.subscribeHeading')) ?></h2>
        <?php if (! $formOpen): ?>
            <?= view('site/partials/form_closed') ?>
        <?php else: ?>
        <?= view('site/partials/form_errors', ['errors' => $errors]) ?>
        <form method="post" action="<?= esc(locale_url('diaspora/stay-informed/subscribe'), 'attr') ?>" class="public-form" novalidate data-testid="subscribe-form">
            <?= view('site/partials/form_guard', ['form' => 'newsletter']) ?>
            <?= view('site/partials/field', ['values' => $values, 'errors' => $errors, 'name' => 'email', 'label' => lang('Forms.email'), 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'maxlength' => 190]) ?>
            <div class="form-check mb-3">
                <input class="form-check-input<?= isset($errors['consent']) ? ' is-invalid' : '' ?>" type="checkbox" id="field-consent" name="consent" value="1"<?= isset($errors['consent']) ? ' aria-invalid="true" aria-describedby="field-consent-error"' : '' ?>>
                <label class="form-check-label" for="field-consent"><?= esc(lang('Forms.subscribeConsent')) ?></label>
                <?php if (isset($errors['consent'])): ?><div class="invalid-feedback" id="field-consent-error"><?= esc($errors['consent']) ?></div><?php endif ?>
            </div>
            <?= view('site/partials/privacy', ['privacyHtml' => $privacyHtml]) ?>
            <button type="submit" class="btn btn-primary"><?= esc(lang('Forms.subscribe')) ?></button>
        </form>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
