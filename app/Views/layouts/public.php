<?php
/**
 * Layout for every public page. The header and navigation are identical on
 * all public pages and stay fixed at the top while scrolling (brief section 1).
 *
 * @var CodeIgniter\View\View $this
 */
$locale = site_locale();
$dir    = site_dir($locale);
$nav    = config(Config\Navigation::class);
$emblem = config(Config\Branding::class)->emblem;
?>
<!doctype html>
<html lang="<?= esc($locale, 'attr') ?>" dir="<?= esc($dir, 'attr') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (ENVIRONMENT !== 'production'): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif ?>
    <title><?= esc($this->renderSection('title')) ?> | <?= esc(lang('Site.ministryName')) ?></title>
<?= view('partials/head_assets', ['dir' => $dir]) ?>
</head>
<body>
    <a class="visually-hidden-focusable skip-link" href="#main"><?= esc(lang('Site.skipToContent')) ?></a>

    <header class="site-header sticky-top" data-testid="site-header">
        <?php if (ENVIRONMENT !== 'production'): ?>
        <div class="dev-banner" role="note"><?= esc(lang('Site.devBanner')) ?></div>
        <?php endif ?>
        <div class="flag-stripe" aria-hidden="true"></div>
        <div class="container-xl site-identity">
            <a class="site-name" href="<?= esc(locale_url(), 'attr') ?>">
                <?php if ($emblem !== ''): ?>
                <img class="site-emblem" src="<?= esc(asset_url($emblem), 'attr') ?>" alt="" width="64" height="64">
                <?php endif ?>
                <span class="site-name-text">
                    <span class="site-name-country"><?= esc(lang('Site.countryName')) ?></span>
                    <span class="site-name-ministry"><?= esc(lang('Site.ministryName')) ?></span>
                </span>
            </a>
        </div>
        <?= view('partials/primary_nav') ?>
    </header>

    <main id="main" class="site-main" tabindex="-1">
        <?= $this->renderSection('content') ?>
    </main>

    <footer class="site-footer">
        <div class="container-xl site-footer-inner">
            <?php if ($emblem !== ''): ?>
            <img class="footer-emblem" src="<?= esc(asset_url($emblem), 'attr') ?>" alt="" width="52" height="52">
            <?php endif ?>
            <nav aria-label="<?= esc(lang('Site.footerNavigation'), 'attr') ?>">
                <ul class="footer-links">
                    <?php if ($nav->archivesPlacement === 'footer'): ?>
                    <li><a href="<?= esc(locale_url($nav->archivesPath), 'attr') ?>"><?= esc(lang('Site.nav.archives')) ?></a></li>
                    <?php endif ?>
                    <li><a href="<?= esc(site_url('login'), 'attr') ?>"><?= esc(lang('Site.staffSignIn')) ?></a></li>
                </ul>
            </nav>
            <p class="footer-name"><?= esc(lang('Site.ministryName')) ?>, <?= esc(lang('Site.countryName')) ?></p>
        </div>
    </footer>

    <?= bootstrap_js() ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
