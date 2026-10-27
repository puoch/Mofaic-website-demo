<?php
$options = config('ContentTypes');
$display = static function (string $column, $value) use ($options): string {
    return match ($column) {
        'category'  => $options->articleCategories[$value] ?? ($options->missionCategories[$value]['label'] ?? (string) $value),
        'section'   => $options->pageSections[$value] ?? (string) $value,
        'is_active' => $value ? 'Yes' : 'No',
        'block_key' => $options->siteBlocks[$value] ?? (string) $value,
        default     => (string) $value,
    };
};
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= esc($definition['label']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h1 class="page-title mb-0"><?= esc($definition['label']) ?></h1>
    <?php if ($canCreate): ?>
    <a class="btn btn-primary" href="<?= esc(site_url('admin/content/' . $type . '/new'), 'attr') ?>">New <?= esc(strtolower($definition['singular'])) ?></a>
    <?php endif ?>
</div>

<form class="row g-2 my-3" method="get" action="<?= esc(current_url(), 'attr') ?>" role="search">
    <div class="col-auto">
        <label class="visually-hidden" for="list-q">Search</label>
        <input class="form-control" type="search" id="list-q" name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search">
    </div>
    <?php if (! empty($definition['status'])): ?>
    <div class="col-auto">
        <label class="visually-hidden" for="list-status">Status</label>
        <select class="form-select" id="list-status" name="status">
            <option value="">Any status</option>
            <option value="draft"<?= $status === 'draft' ? ' selected' : '' ?>>Draft</option>
            <option value="published"<?= $status === 'published' ? ' selected' : '' ?>>Published</option>
        </select>
    </div>
    <?php endif ?>
    <div class="col-auto"><button class="btn btn-outline-secondary" type="submit">Filter</button></div>
</form>

<?php if ($rows === []): ?>
<p class="section-pending">Nothing here yet.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm align-middle" data-testid="content-list">
        <thead>
            <tr>
                <?php foreach ($definition['list'] as $column): ?>
                <th scope="col"><?= esc($labels[$column] ?? $column) ?></th>
                <?php endforeach ?>
                <th scope="col"><span class="visually-hidden">Actions</span></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $i => $row): ?>
            <tr>
                <?php foreach ($definition['list'] as $n => $column): ?>
                <td>
                    <?php if ($n === 0): ?>
                    <a href="<?= esc(site_url('admin/content/' . $type . '/' . $row['id']), 'attr') ?>"><?= esc($display($column, $row[$column] ?? '')) ?></a>
                    <?php elseif ($column === 'status'): ?>
                    <span class="badge <?= $row['status'] === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= esc($row['status']) ?></span>
                    <?php else: ?>
                    <?= esc($display($column, $row[$column] ?? '')) ?>
                    <?php endif ?>
                </td>
                <?php endforeach ?>
                <td class="text-end"><?= $editable[$i] ? '<span class="text-muted small">You can edit</span>' : '<span class="text-muted small">View only</span>' ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
<p>
    <?php if ($page > 1): ?><a href="<?= esc(current_url() . '?' . http_build_query(['q' => $q, 'status' => $status, 'page' => $page - 1]), 'attr') ?>">Previous</a><?php endif ?>
    Page <?= esc($page) ?> of <?= esc($pages) ?>
    <?php if ($page < $pages): ?><a href="<?= esc(current_url() . '?' . http_build_query(['q' => $q, 'status' => $status, 'page' => $page + 1]), 'attr') ?>">Next</a><?php endif ?>
</p>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
