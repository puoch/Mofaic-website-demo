<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Revision history<?= $this->endSection() ?>

<?= $this->section('content') ?>
<p><a href="<?= esc(site_url('admin/content/' . $type . '/' . $record['id']), 'attr') ?>">← <?= esc($record[$definition['titleField']]) ?></a></p>
<h1 class="page-title">Revision history</h1>
<p class="text-muted">Every saved version is kept until MOFAIC sets a retention period. Restoring brings back the text and settings of that version as a new version; it does not change whether the item is published.</p>

<?php if ($revisions === []): ?>
<p class="section-pending">No revisions yet. One is stored each time the item is saved.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm align-middle" data-testid="revision-list">
        <thead><tr><th scope="col">Revision</th><th scope="col">Saved (UTC)</th><th scope="col">Staff account</th><th scope="col">Note</th><th scope="col">Title in this version</th><th scope="col"></th></tr></thead>
        <tbody>
            <?php foreach ($revisions as $i => $revision): ?>
            <?php $data = json_decode($revision['snapshot'], true) ?: []; ?>
            <tr>
                <td><?= esc($revision['revision_number']) ?><?= $i === 0 ? ' <span class="badge text-bg-light">current</span>' : '' ?></td>
                <td><?= esc($revision['created_at']) ?></td>
                <td><?= esc($revision['created_by'] ?? '') ?></td>
                <td><?= esc($revision['note'] ?? '') ?></td>
                <td><?= esc($data[$definition['titleField']] ?? '') ?></td>
                <td>
                    <?php if ($canRestore && $i > 0): ?>
                    <form method="post" action="<?= esc(site_url('admin/content/' . $type . '/' . $record['id'] . '/revisions/' . $revision['id'] . '/restore'), 'attr') ?>">
                        <?= csrf_field() ?><button class="btn btn-sm btn-outline-primary" type="submit">Restore</button>
                    </form>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>
<?= $this->endSection() ?>
