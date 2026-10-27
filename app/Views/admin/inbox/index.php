<?php
$title = $inbox === 'inquiries' ? 'Public Inquiries Inbox' : 'Diaspora inbox';
$query = static fn (array $extra) => '?' . http_build_query(array_filter(['type' => $type, 'status' => $status, 'mine' => $mine ? '1' : ''] + $extra, static fn ($v) => $v !== '' && $v !== null));
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title"><?= esc($title) ?></h1>

<?php if (count($types) > 1): ?>
<ul class="nav nav-tabs mb-3" data-testid="type-filter">
    <?php foreach ($types as $key => $definition): ?>
    <li class="nav-item"><a class="nav-link<?= $key === $type ? ' active' : '' ?>"<?= $key === $type ? ' aria-current="page"' : '' ?> href="<?= esc(site_url('admin/' . $inbox) . '?type=' . $key, 'attr') ?>"><?= esc($definition['label']) ?>s</a></li>
    <?php endforeach ?>
</ul>
<?php endif ?>

<form method="get" class="row g-2 align-items-end mb-3">
    <input type="hidden" name="type" value="<?= esc($type, 'attr') ?>">
    <div class="col-auto">
        <label class="form-label" for="filter-status">Status</label>
        <select class="form-select form-select-sm" id="filter-status" name="status">
            <option value="">Any</option>
            <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= esc($key, 'attr') ?>"<?= $key === $status ? ' selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="col-auto form-check ms-2">
        <input class="form-check-input" type="checkbox" id="filter-mine" name="mine" value="1"<?= $mine ? ' checked' : '' ?>>
        <label class="form-check-label" for="filter-mine">Assigned to me</label>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary" type="submit">Filter</button></div>
</form>

<?php if ($rows === []): ?>
<p class="section-pending" data-testid="inbox-empty">Nothing here.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm align-middle" data-testid="inbox-list">
        <thead><tr>
            <th scope="col">Reference</th>
            <th scope="col">Received</th>
            <?php if ($type === 'registration'): ?><th scope="col">Country of residence</th><?php endif ?>
            <?php if ($type === 'contribution'): ?><th scope="col">Category</th><?php endif ?>
            <?php if ($type === 'inquiry'): ?><th scope="col">Subject</th><?php endif ?>
            <th scope="col">Status</th>
            <th scope="col">Assigned to</th>
        </tr></thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <th scope="row"><a href="<?= esc(site_url("admin/{$inbox}/{$type}/{$row['id']}"), 'attr') ?>"><?= esc($row['reference']) ?></a></th>
                <td><?= esc((string) $row['created_at']) ?> UTC</td>
                <?php if ($type === 'registration'): ?><td><?= esc($countries[$row['country_id']] ?? '') ?></td><?php endif ?>
                <?php if ($type === 'contribution'): ?><td><?= esc($categories[$row['category']] ?? $row['category']) ?></td><?php endif ?>
                <?php if ($type === 'inquiry'): ?><td><?= esc((string) $row['subject']) ?></td><?php endif ?>
                <td><?= esc($statuses[$row['status']] ?? $row['status']) ?></td>
                <td><?= esc($row['assigned_to'] !== null ? ($staff[(int) $row['assigned_to']] ?? '') : '') ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php if ($pages > 1): ?>
<nav aria-label="Pages"><ul class="pagination pagination-sm">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <li class="page-item<?= $p === $page ? ' active' : '' ?>"><a class="page-link" href="<?= esc(site_url('admin/' . $inbox) . $query(['page' => $p]), 'attr') ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a></li>
    <?php endfor ?>
</ul></nav>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
