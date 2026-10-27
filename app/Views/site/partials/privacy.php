<?php
/**
 * The Ministry's approved privacy notice, entered as a fixed text block.
 *
 * @var string $privacyHtml
 */
?>
<section class="privacy-notice" aria-labelledby="privacy-heading" data-testid="privacy-notice">
    <h2 id="privacy-heading" class="h6"><?= esc(lang('Forms.privacyHeading')) ?></h2>
    <div class="prose small"><?= $privacyHtml ?></div>
</section>
