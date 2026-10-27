<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Publishing<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title">Publishing</h1>

<div class="table-responsive">
    <table class="table table-sm align-middle" data-testid="publishing-summary">
        <thead><tr><th scope="col">Content</th><th scope="col">Published</th><th scope="col">Draft</th><th scope="col"></th></tr></thead>
        <tbody>
            <?php foreach ($summary as $key => $item): ?>
            <tr>
                <th scope="row"><a href="<?= esc(site_url('admin/content/' . $key), 'attr') ?>"><?= esc($item['label']) ?></a></th>
                <td><?= esc($item['counts']['published']) ?></td>
                <td><a href="<?= esc(site_url('admin/content/' . $key . '?status=draft'), 'attr') ?>"><?= esc($item['counts']['draft']) ?></a></td>
                <td><?php if ($item['canCreate']): ?><a class="btn btn-sm btn-outline-primary" href="<?= esc(site_url('admin/content/' . $key . '/new'), 'attr') ?>">New</a><?php endif ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<h2 class="h5 mt-4">Recently changed</h2>
<?php if ($recent === []): ?>
<p class="section-pending">Nothing has been created yet.</p>
<?php else: ?>
<ul class="list-unstyled">
    <?php foreach ($recent as $item): ?>
    <li class="mb-1">
        <span class="badge <?= $item['status'] === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= esc($item['status']) ?></span>
        <span class="text-muted"><?= esc($item['typeLabel']) ?>:</span>
        <a href="<?= esc(site_url('admin/content/' . $item['type'] . '/' . $item['id']), 'attr') ?>"><?= esc($item['title']) ?></a>
        <span class="text-muted small"><?= esc($item['updated_at']) ?> UTC</span>
    </li>
    <?php endforeach ?>
</ul>
<?php endif ?>

<?php if ($canAudit): ?>
<h2 class="h5 mt-4">Publishing log</h2>
<?php if ($log === []): ?>
<p class="section-pending">No publishing activity yet.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm" data-testid="publishing-log">
        <thead><tr><th scope="col">When (UTC)</th><th scope="col">Event</th><th scope="col">Item</th><th scope="col">Staff account</th></tr></thead>
        <tbody>
            <?php foreach ($log as $event): ?>
            <?php $details = json_decode((string) $event['details'], true) ?: []; ?>
            <tr>
                <td><?= esc($event['occurred_at']) ?></td>
                <td><code><?= esc($event['event']) ?></code></td>
                <td><?= esc(($event['subject_type'] ?? '') . ' #' . ($event['subject_id'] ?? '')) ?> <?= esc($details['title'] ?? '') ?></td>
                <td><?= esc($event['actor_user_id'] ?? '') ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>
<?php endif ?>
<?= $this->endSection() ?>
