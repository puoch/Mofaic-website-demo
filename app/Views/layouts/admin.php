<?php
/** @var CodeIgniter\View\View $this */
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc($this->renderSection('title')) ?> | <?= esc(lang('Admin.title')) ?></title>
<?= view('partials/head_assets', ['dir' => 'ltr']) ?>
</head>
<body>
    <a class="visually-hidden-focusable skip-link" href="#main"><?= esc(lang('Site.skipToContent')) ?></a>
    <header class="site-header">
        <?php if (ENVIRONMENT !== 'production'): ?>
        <div class="dev-banner" role="note"><?= esc(lang('Site.devBanner')) ?></div>
        <?php endif ?>
        <nav class="navbar navbar-expand site-nav" aria-label="<?= esc(lang('Admin.title'), 'attr') ?>">
            <div class="container-xl">
                <a class="navbar-brand site-name-ministry" href="<?= esc(site_url('admin'), 'attr') ?>"><?= esc(lang('Admin.title')) ?></a>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= esc(site_url('admin'), 'attr') ?>"><?= esc(lang('Admin.dashboard')) ?></a></li>
                    <?php if (auth()->user()?->can('audit.view')): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= esc(site_url('admin/audit'), 'attr') ?>"><?= esc(lang('Admin.auditLog')) ?></a></li>
                    <?php endif ?>
                </ul>
                <a class="btn btn-outline-secondary btn-sm" href="<?= esc(site_url('logout'), 'attr') ?>"><?= esc(lang('Admin.signOut')) ?></a>
            </div>
        </nav>
    </header>
    <main id="main" class="site-main container-xl page-body" tabindex="-1">
        <?= $this->renderSection('content') ?>
    </main>
</body>
</html>
