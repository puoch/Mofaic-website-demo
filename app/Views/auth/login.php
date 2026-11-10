<?php
/**
 * Staff sign-in form. Based on Shield's login view, with links to
 * "Forgot password" and "Request an account" (2026-11-10).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?><?= $this->endSection() ?>

<?= $this->section('main') ?>

    <div class="container d-flex justify-content-center auth-card-wrap">
        <div class="card col-12 col-md-5 shadow-sm">
            <div class="card-body">
                <h1 class="card-title h4 mb-4"><?= lang('Auth.login') ?></h1>

                <?php if (session('error') !== null) : ?>
                    <div class="alert alert-danger" role="alert"><?= esc(session('error')) ?></div>
                <?php elseif (session('errors') !== null) : ?>
                    <div class="alert alert-danger" role="alert">
                        <?php if (is_array(session('errors'))) : ?>
                            <?php foreach (session('errors') as $error) : ?>
                                <?= esc($error) ?>
                                <br>
                            <?php endforeach ?>
                        <?php else : ?>
                            <?= esc(session('errors')) ?>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <?php if (session('message') !== null) : ?>
                    <div class="alert alert-success" role="status"><?= esc(session('message')) ?></div>
                <?php endif ?>

                <form action="<?= url_to('login') ?>" method="post">
                    <?= csrf_field() ?>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="floatingEmailInput" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required>
                        <label for="floatingEmailInput"><?= lang('Auth.email') ?></label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="floatingPasswordInput" name="password" inputmode="text" autocomplete="current-password" placeholder="<?= lang('Auth.password') ?>" required>
                        <label for="floatingPasswordInput"><?= lang('Auth.password') ?></label>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                        <?php if (setting('Auth.sessionConfig')['allowRemembering']): ?>
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="rememberMe" aria-describedby="rememberMeHint"<?php if (old('remember')): ?> checked<?php endif ?>>
                            <label class="form-check-label" for="rememberMe"><?= lang('Auth.rememberMe') ?></label>
                            <div id="rememberMeHint" class="form-text mt-0"><?= esc(lang('StaffAccess.rememberHint', [(int) (setting('Auth.sessionConfig')['rememberLength'] / DAY)])) ?></div>
                        </div>
                        <?php endif ?>
                        <a href="<?= esc(site_url('login/forgot-password'), 'attr') ?>" data-testid="forgot-password-link"><?= esc(lang('StaffAccess.forgotLink')) ?></a>
                    </div>

                    <div class="d-grid col-12 col-md-8 mx-auto m-3">
                        <button type="submit" class="btn btn-primary btn-block"><?= lang('Auth.login') ?></button>
                    </div>

                    <p class="text-center mb-0"><?= esc(lang('StaffAccess.needAccount')) ?> <a href="<?= esc(site_url('login/request-account'), 'attr') ?>" data-testid="request-account-link"><?= esc(lang('StaffAccess.requestLink')) ?></a></p>
                </form>
            </div>
        </div>
    </div>

<?= $this->endSection() ?>
