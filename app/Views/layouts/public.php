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
$social = footer_social_links();
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
    <div id="top"></div>
    <a class="visually-hidden-focusable skip-link" href="#main"><?= esc(lang('Site.skipToContent')) ?></a>

    <header class="site-header sticky-top" data-testid="site-header">
        <?php if (ENVIRONMENT !== 'production'): ?>
        <div class="dev-banner" role="note"><?= esc(lang('Site.devBanner')) ?></div>
        <?php endif ?>
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
            <div class="footer-start">
                <div class="footer-start-row">
                    <ul class="footer-quick" data-testid="footer-quick-links">
                        <li><a href="<?= esc(locale_url('sitemap'), 'attr') ?>"><?= esc(lang('Site.sitemap')) ?></a></li>
                        <li><a href="<?= esc(locale_url('contacts'), 'attr') ?>"><?= esc(lang('Site.footerContact')) ?></a></li>
                    </ul>
                    <?php if ($social !== []): ?>
                    <ul class="footer-social" aria-label="<?= esc(lang('Site.footerSocial'), 'attr') ?>" data-testid="footer-social">
                        <?php foreach ($social as $account): ?>
                        <li>
                            <a href="<?= esc($account['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer">
                                <?= view('partials/social_icon', ['icon' => $account['icon']]) ?>
                                <span class="visually-hidden"><?= esc($account['label']) ?> <?= esc(lang('Site.opensInNewTab')) ?></span>
                            </a>
                        </li>
                        <?php endforeach ?>
                    </ul>
                    <?php endif ?>
                </div>
                <p class="footer-copyright"><?= esc(lang('Site.footerCopyright')) ?></p>
            </div>
            <nav aria-label="<?= esc(lang('Site.footerNavigation'), 'attr') ?>">
                <ul class="footer-links">
                    <?php foreach ($nav->footer as $link): ?>
                    <li><a href="<?= esc(locale_url($link['path']), 'attr') ?>"><?= esc(lang($link['label'])) ?></a></li>
                    <?php endforeach ?>
                    <?php if ($nav->archivesPlacement === 'footer'): ?>
                    <li><a href="<?= esc(locale_url($nav->archivesPath), 'attr') ?>"><?= esc(lang('Site.nav.archives')) ?></a></li>
                    <?php endif ?>
                    <li><a href="<?= esc(site_url('login'), 'attr') ?>"><?= esc(lang('Site.staffSignIn')) ?></a></li>
                </ul>
            </nav>
            <a class="back-to-top" href="#top" data-testid="back-to-top">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false">
                    <path d="M5 15l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="visually-hidden"><?= esc(lang('Site.backToTop')) ?></span>
            </a>
        </div>
    </footer>

    <?= bootstrap_js() ?>
    <script src="<?= esc(asset_url('assets/js/site-search.js'), 'attr') ?>" defer></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
