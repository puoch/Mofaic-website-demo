<?php
/**
 * Replaces Shield's default sign-in layout, which loads Bootstrap from a
 * public CDN without integrity checks. This one uses the site's own asset
 * tags, which follow assets.bootstrapSource (PD-17).
 *
 * The national coat of arms sits centred above every staff sign-in form.
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
