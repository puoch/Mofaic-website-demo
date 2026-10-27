<?php
/**
 * @var string                     $heading
 * @var string                     $section
 * @var list<array<string, mixed>> $tree
 * @var bool                       $expandable Services categories open and close
 */
$renderTree = static function (array $pages, string $section) use (&$renderTree): string {
    $html = '<ul class="page-tree">';
    foreach ($pages as $page) {
        $html .= '<li><a href="' . esc(locale_url($section . '/' . $page['path']), 'attr') . '">' . esc($page['title']) . '</a>';
        if ($page['children'] !== []) {
            $html .= $renderTree($page['children'], $section);
        }
        $html .= '</li>';
    }

    return $html . '</ul>';
};
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[$heading]]]) ?>
    <h1 class="page-title"><?= esc($heading) ?></h1>

    <?php if ($tree === []): ?>
        <?= view('site/partials/empty') ?>
    <?php elseif ($expandable): ?>
        <div class="service-categories" data-testid="service-categories">
        <?php foreach ($tree as $category): ?>
            <details class="service-category" open>
                <summary><h2 class="h4 d-inline"><?= esc($category['title']) ?></h2></summary>
                <p><a href="<?= esc(locale_url($section . '/' . $category['path']), 'attr') ?>"><?= esc($category['title']) ?></a></p>
                <?php if ($category['children'] !== []): ?><?= $renderTree($category['children'], $section) ?><?php endif ?>
            </details>
        <?php endforeach ?>
        </div>
    <?php else: ?>
        <ul class="section-list">
        <?php foreach ($tree as $page): ?>
            <li>
                <h2 class="h5"><a href="<?= esc(locale_url($section . '/' . $page['path']), 'attr') ?>"><?= esc($page['title']) ?></a></h2>
                <?php if (! empty($page['summary'])): ?><p><?= esc($page['summary']) ?></p><?php endif ?>
                <?php if ($page['children'] !== []): ?><?= $renderTree($page['children'], $section) ?><?php endif ?>
            </li>
        <?php endforeach ?>
        </ul>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
