<?php
/**
 * @var list<array{0: string, 1?: string|null}> $crumbs label and optional URL; the last one is the current page
 */
?>
<nav aria-label="<?= esc(lang('Public.breadcrumb'), 'attr') ?>">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= esc(locale_url(), 'attr') ?>"><?= esc(lang('Site.nav.home')) ?></a></li>
        <?php foreach ($crumbs as $i => $crumb): ?>
            <?php if ($i === array_key_last($crumbs) || empty($crumb[1])): ?>
            <li class="breadcrumb-item active" aria-current="page"><?= esc($crumb[0]) ?></li>
            <?php else: ?>
            <li class="breadcrumb-item"><a href="<?= esc($crumb[1], 'attr') ?>"><?= esc($crumb[0]) ?></a></li>
            <?php endif ?>
        <?php endforeach ?>
    </ol>
</nav>
