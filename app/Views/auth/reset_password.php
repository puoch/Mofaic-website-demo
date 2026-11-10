<?php
/**
 * Choose a new password from an emailed link. $token is '' when the link
 * is unknown, used or expired.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $token
 * @var array<string, string> $errors
 */
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>
<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= esc(lang('StaffAccess.resetTitle')) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="container d-flex justify-content-center auth-card-wrap">
        <div class="card col-12 col-md-5 shadow-sm">
            <div class="card-body">
                <h1 class="card-title h4 mb-3"><?= esc(lang('StaffAccess.resetTitle')) ?></h1>

                <?php if ($token === ''): ?>
                <div class="alert alert-warning" role="alert" data-testid="link-invalid"><?= esc(lang('StaffAccess.linkInvalid')) ?></div>
                <p class="text-center"><a href="<?= esc(site_url('login/forgot-password'), 'attr') ?>"><?= esc(lang('StaffAccess.forgotTitle')) ?></a></p>
                <?php else: ?>
                <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert" tabindex="-1" id="form-errors">
                    <?php foreach ($errors as $error): ?><?= esc($error) ?><br><?php endforeach ?>
                </div>
                <?php endif ?>
                <p><?= esc(lang('StaffAccess.resetIntro')) ?></p>
                <form action="<?= esc(site_url('login/reset-password'), 'attr') ?>" method="post" data-testid="reset-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="newPassword"><?= esc(lang('StaffAccess.newPassword')) ?></label>
                        <input type="password" class="form-control<?= $invalid('password') ?>" id="newPassword" name="password" autocomplete="new-password" aria-describedby="newPasswordHint" required>
                        <div id="newPasswordHint" class="form-text"><?= esc(lang('StaffAccess.resetHint')) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirmPassword"><?= esc(lang('StaffAccess.confirmPassword')) ?></label>
                        <input type="password" class="form-control<?= $invalid('password_confirm') ?>" id="confirmPassword" name="password_confirm" autocomplete="new-password" required>
                    </div>
                    <div class="d-grid col-12 col-md-8 mx-auto my-3">
                        <button type="submit" class="btn btn-primary"><?= esc(lang('StaffAccess.savePassword')) ?></button>
                    </div>
                </form>
                <?php endif ?>

                <p class="text-center mb-0"><a href="<?= esc(site_url('login'), 'attr') ?>"><?= esc(lang('StaffAccess.backToSignIn')) ?></a></p>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>
