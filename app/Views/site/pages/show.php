<?php
/**
 * @var array<string, mixed>            $page
 * @var list<array<string, mixed>>      $ancestors
 * @var list<array<string, mixed>>      $children
 * @var list<array<string, mixed>>|null $orgUnits only on the organogram page
 */
$crumbs = [[$heading, locale_url($section)]];
foreach ($ancestors as $ancestor) {
    $crumbs[] = [$ancestor['title'], locale_url($section . '/' . $ancestor['path'])];
}
$crumbs[] = [$page['title']];

// Interactive organogram: units with sub-units open and close (no JavaScript needed).
$renderOrg = static function (array $units) use (&$renderOrg): string {
    $html = '<ul class="org-tree">';
    foreach ($units as $unit) {
        $label = '<span class="org-name">' . esc($unit['name']) . '</span>';
        if (! empty($unit['head_title'])) {
            $label .= ' <span class="org-head">' . esc($unit['head_title']) . '</span>';
        }
        $html .= $unit['children'] === []
            ? '<li><span class="org-unit">' . $label . '</span></li>'
            : '<li><details open><summary class="org-unit">' . $label . '</summary>' . $renderOrg($unit['children']) . '</details></li>';
    }

    return $html . '</ul>';
};
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($page['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => $crumbs]) ?>
    <article class="content-page">
        <h1 class="page-title"><?= esc($page['title']) ?></h1>
        <?php if (! empty($page['summary'])): ?><p class="lead"><?= esc($page['summary']) ?></p><?php endif ?>
        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>

        <?php if ($orgUnits !== null && $orgUnits !== []): ?>
        <section class="organogram" aria-labelledby="organogram-heading" data-testid="organogram">
            <h2 id="organogram-heading" class="h4"><?= esc($page['title']) ?></h2>
            <?= $renderOrg($orgUnits) ?>
        </section>
        <?php endif ?>

        <?= view('site/partials/media', ['media' => $media]) ?>

        <?php if ($links !== []): ?>
        <section class="media-section" aria-labelledby="external-links">
            <h2 id="external-links" class="h5"><?= esc(lang('Public.externalLinks')) ?></h2>
            <ul class="link-list">
                <?php foreach ($links as $link): ?>
                <li><a href="<?= esc($link['url'], 'attr') ?>" rel="noopener" target="_blank"><?= esc($link['label']) ?> <span class="visually-hidden"><?= esc(lang('Public.opensExternal')) ?></span></a></li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endif ?>

        <?php if ($children !== []): ?>
        <section class="media-section" aria-labelledby="child-pages">
            <h2 id="child-pages" class="h5"><?= esc(lang('Public.inThisSection')) ?></h2>
            <ul class="link-list">
                <?php foreach ($children as $child): ?>
                <li><a href="<?= esc(locale_url($section . '/' . $child['path']), 'attr') ?>"><?= esc($child['title']) ?></a></li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endif ?>
    </article>
</div>
<?= $this->endSection() ?>
