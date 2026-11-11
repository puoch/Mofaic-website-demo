<?php
/**
 * Replaces Shield's default sign-in layout, which loads Bootstrap from a
 * public CDN without integrity checks. This one uses the site's own asset
 * tags, which follow assets.bootstrapSource (PD-17).
 *
 * The national coat of arms sits centred above every staff sign-in form,
 * with a "Back to the website" button above it (2026-11-11).
 *
 * @var CodeIgniter\View\View $this
 */
$emblem = config(Config\Branding::class)->emblem;
?>
<!doctype html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php // Light design only: stops phone browsers (e.g. Chrome auto dark mode) recolouring the pages, photos and emblems. ?>
    <meta name="color-scheme" content="only light">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $this->renderSection('title') ?></title>
<?= view('partials/head_assets', ['dir' => 'ltr']) ?>
    <?= $this->renderSection('pageStyles') ?>
</head>
<body class="bg-light">
    <?php if (ENVIRONMENT !== 'production'): ?>
    <div class="dev-banner" role="note"><?= esc(lang('Site.devBanner')) ?></div>
    <?php endif ?>
    <main role="main" class="container">
        <div class="auth-return">
            <a class="btn btn-outline-primary" href="<?= esc(site_url(config('App')->defaultLocale), 'attr') ?>" data-testid="back-to-website">
                <svg aria-hidden="true" focusable="false" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M7.7 2.3a1 1 0 0 1 0 1.4L4.4 7H14a1 1 0 1 1 0 2H4.4l3.3 3.3a1 1 0 1 1-1.4 1.4l-5-5a1 1 0 0 1 0-1.4l5-5a1 1 0 0 1 1.4 0z"/></svg>
                <?= esc(lang('Site.backToWebsite')) ?>
            </a>
        </div>
        <?php if ($emblem !== ''): ?>
        <div class="auth-emblem">
            <img src="<?= esc(asset_url($emblem), 'attr') ?>" alt="<?= esc(lang('Site.emblemAlt'), 'attr') ?>" width="140" height="138">
        </div>
        <?php endif ?>
        <?= $this->renderSection('main') ?>
    </main>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
