<?php
/**
 * Bootstrap comes from this server or from the CDN, set by
 * assets.bootstrapSource (PD-17, Config\Assets). Bootstrap 5.3.8 is vendored
 * in public/assets/vendor; see docs/versions.md.
 *
 * @var string $dir 'ltr' or 'rtl'
 */
?>
    <?= bootstrap_css($dir) ?>

    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
