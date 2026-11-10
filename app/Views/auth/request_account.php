<?php
/**
 * "Request an account" for Ministry staff. Closed until the privacy notice
 * is published, like the public forms.
 *
 * @var CodeIgniter\View\View $this
 * @var bool                  $sent
 * @var string                $privacy approved privacy notice HTML, or ''
 * @var array<string, string> $errors
 * @var array<string, string> $values
 * @var string|null           $form    spam-check message
 */
$fields = [
    'full_name' => ['label' => lang('StaffAccess.fullName'), 'type' => 'text', 'max' => 120, 'auto' => 'name'],
    'email'     => ['label' => lang('StaffAccess.workEmail'), 'type' => 'email', 'max' => 254, 'auto' => 'email'],
    'office'    => ['label' => lang('StaffAccess.office'), 'type' => 'text', 'max' => 160, 'auto' => 'organization'],
    'job_title' => ['label' => lang('StaffAccess.jobTitle'), 'type' => 'text', 'max' => 120, 'auto' => 'organization-title'],
];
?>
<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= esc(lang('StaffAccess.requestTitle')) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="container d-flex justify-content-center auth-card-wrap">
        <div class="card col-12 col-md-7 col-lg-6 shadow-sm">
            <div class="card-body">
                <h1 class="card-title h4 mb-3"><?= esc(lang('StaffAccess.requestTitle')) ?></h1>

                <?php if ($sent): ?>
                <div class="alert alert-success" role="status" data-testid="request-sent"><?= esc(lang('StaffAccess.requestSent')) ?></div>
                <?php elseif ($privacy === ''): ?>
                <?= view('site/partials/form_closed') ?>
                <?php else: ?>
                <p><?= esc(lang('StaffAccess.requestIntro')) ?></p>

                <?php if ($errors !== [] || $form !== null): ?>
                <div class="alert alert-danger" role="alert">
                    <?php if ($form !== null): ?><?= esc($form) ?><br><?php endif ?>
                    <?php foreach ($errors as $error): ?><?= esc($error) ?><br><?php endforeach ?>
                </div>
                <?php endif ?>

                <form action="<?= esc(site_url('login/request-account'), 'attr') ?>" method="post" data-testid="request-form" novalidate>
                    <?= view('site/partials/form_guard', ['form' => 'account-request']) ?>
                    <?php foreach ($fields as $name => $field): ?>
                    <div class="mb-3">
                        <label class="form-label" for="req-<?= $name ?>"><?= esc($field['label']) ?></label>
                        <input type="<?= $field['type'] ?>" class="form-control<?= isset($errors[$name]) ? ' is-invalid' : '' ?>" id="req-<?= $name ?>" name="<?= $name ?>" value="<?= esc($values[$name] ?? '', 'attr') ?>" maxlength="<?= $field['max'] ?>" autocomplete="<?= $field['auto'] ?>" required<?= isset($errors[$name]) ? ' aria-invalid="true"' : '' ?>>
                    </div>
                    <?php endforeach ?>
                    <div class="mb-3">
                        <label class="form-label" for="req-reason"><?= esc(lang('StaffAccess.reason')) ?></label>
                        <textarea class="form-control<?= isset($errors['reason']) ? ' is-invalid' : '' ?>" id="req-reason" name="reason" rows="3" maxlength="1000" aria-describedby="req-reason-hint" required<?= isset($errors['reason']) ? ' aria-invalid="true"' : '' ?>><?= esc($values['reason'] ?? '') ?></textarea>
                        <div id="req-reason-hint" class="form-text"><?= esc(lang('Forms.freeTextWarning')) ?></div>
                    </div>

                    <h2 class="h6 mt-4"><?= esc(lang('Forms.privacyHeading')) ?></h2>
                    <div class="small mb-3" data-testid="privacy-notice"><?= $privacy ?></div>

                    <div class="d-grid col-12 col-md-8 mx-auto my-3">
                        <button type="submit" class="btn btn-primary"><?= esc(lang('StaffAccess.sendRequest')) ?></button>
                    </div>
                </form>
                <?php endif ?>

                <p class="text-center mb-0"><a href="<?= esc(site_url('login'), 'attr') ?>"><?= esc(lang('StaffAccess.backToSignIn')) ?></a></p>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>
