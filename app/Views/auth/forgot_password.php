<?php
/**
 * "Forgot password": asks for the sign-in email address.
 *
 * @var CodeIgniter\View\View $this
 * @var bool $sent
 */
?>
<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= esc(lang('StaffAccess.forgotTitle')) ?><?= $this->endSection() ?>

<?= $this->section('main') ?>
    <div class="container d-flex justify-content-center auth-card-wrap">
        <div class="card col-12 col-md-5 shadow-sm">
            <div class="card-body">
                <h1 class="card-title h4 mb-3"><?= esc(lang('StaffAccess.forgotTitle')) ?></h1>

                <?php if (session('error') !== null): ?>
                <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
                <?php endif ?>

                <?php if ($sent): ?>
                <div class="alert alert-success" role="status" data-testid="reset-sent"><?= esc(lang('StaffAccess.forgotSent')) ?></div>
                <?php else: ?>
                <p><?= esc(lang('StaffAccess.forgotIntro')) ?></p>
                <form action="<?= esc(site_url('login/forgot-password'), 'attr') ?>" method="post" data-testid="forgot-form">
                    <?= csrf_field() ?>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="resetEmail" name="email" inputmode="email" autocomplete="email" placeholder="<?= esc(lang('StaffAccess.email'), 'attr') ?>" maxlength="254" required>
                        <label for="resetEmail"><?= esc(lang('StaffAccess.email')) ?></label>
                    </div>
                    <div class="d-grid col-12 col-md-8 mx-auto my-3">
                        <button type="submit" class="btn btn-primary"><?= esc(lang('StaffAccess.sendLink')) ?></button>
                    </div>
                </form>
                <?php endif ?>

                <p class="text-center mb-0"><a href="<?= esc(site_url('login'), 'attr') ?>"><?= esc(lang('StaffAccess.backToSignIn')) ?></a></p>
            </div>
        </div>
    </div>
<?= $this->endSection() ?>
