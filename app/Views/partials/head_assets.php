<?php
/**
 * Front-end assets are served from this server, never a public CDN
 * (on-premises requirement, brief section 1). Bootstrap 5.3.8 is vendored in
 * public/assets/vendor; see docs/versions.md.
 *
 * @var string $dir 'ltr' or 'rtl'
 */
$bootstrapCss = $dir === 'rtl' ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css';
?>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-5.3.8/css/' . $bootstrapCss) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>">
