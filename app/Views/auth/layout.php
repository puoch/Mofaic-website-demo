<?php
/**
 * Replaces Shield's default sign-in layout, which loads Bootstrap from a
 * public CDN. Everything here is served locally.
 *
 * @var CodeIgniter\View\View $this
 */
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
        <?= $this->renderSection('main') ?>
    </main>
<?= $this->renderSection('pageScripts') ?>
</body>
</html>
