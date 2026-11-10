<?php
/**
 * Sitemap: every section in the approved navigation, then the footer pages.
 *
 * @var list<array<string, mixed>> $primary
 * @var Config\Navigation          $footer
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.sitemap')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.sitemap')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Site.sitemap')) ?></h1>
    <ul class="sitemap-list" data-testid="sitemap">
        <?php foreach ($primary as $item): ?>
        <li>
            <a href="<?= esc(locale_url($item['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $item['key'])) ?></a>
            <?php if (! empty($item['children'])): ?>
            <ul>
                <?php foreach ($item['children'] as $group): ?>
                <li>
                    <a href="<?= esc(locale_url($group['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $group['key'])) ?></a>
                    <?php if (! empty($group['children'])): ?>
                    <ul>
                        <?php foreach ($group['children'] as $child): ?>
                        <li><a href="<?= esc(locale_url($child['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $child['key'])) ?></a></li>
                        <?php endforeach ?>
                    </ul>
                    <?php endif ?>
                </li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
        </li>
        <?php endforeach ?>
        <?php foreach ($footer->footer as $link): ?>
        <li><a href="<?= esc(locale_url($link['path']), 'attr') ?>"><?= esc(lang($link['label'])) ?></a></li>
        <?php endforeach ?>
        <?php if ($footer->archivesPlacement === 'footer'): ?>
        <li><a href="<?= esc(locale_url($footer->archivesPath), 'attr') ?>"><?= esc(lang('Site.nav.archives')) ?></a></li>
        <?php endif ?>
        <li><a href="<?= esc(locale_url('search'), 'attr') ?>"><?= esc(lang('Site.search')) ?></a></li>
    </ul>
</div>
<?= $this->endSection() ?>
