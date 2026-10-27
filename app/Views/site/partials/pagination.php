<?php
/**
 * @var int                  $page
 * @var int                  $pages
 * @var array<string, mixed> $query current filters
 */
if ($pages <= 1) {
    return;
}
$link = static fn (int $n): string => current_url() . '?' . http_build_query(array_filter($query + ['page' => $n], static fn ($v) => $v !== '' && $v !== null));
?>
<nav aria-label="<?= esc(lang('Public.pagination'), 'attr') ?>">
    <ul class="pagination">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="<?= esc($link($page - 1), 'attr') ?>"><?= esc(lang('Public.previous')) ?></a></li>
        <?php endif ?>
        <li class="page-item disabled"><span class="page-link"><?= esc(lang('Public.pageOf', [$page, $pages])) ?></span></li>
        <?php if ($page < $pages): ?>
        <li class="page-item"><a class="page-link" href="<?= esc($link($page + 1), 'attr') ?>"><?= esc(lang('Public.next')) ?></a></li>
        <?php endif ?>
    </ul>
</nav>
