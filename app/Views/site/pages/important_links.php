<?php
/**
 * Services > Important links. The links are the active Quick links from the admin.
 *
 * @var string                     $heading
 * @var list<array<string, mixed>> $links
 */
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Public.quickLinks')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[$heading, locale_url('services')], [lang('Public.quickLinks')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Public.quickLinks')) ?></h1>

    <?php if ($links === []): ?>
        <?= view('site/partials/empty') ?>
    <?php else: ?>
    <ul class="link-list" data-testid="important-links">
        <?php foreach ($links as $link): ?>
        <li><a href="<?= esc($link['url'], 'attr') ?>"><?= esc($link['label']) ?></a></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
