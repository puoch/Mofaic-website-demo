<?php
$fields = match ($type) {
    'inquiry' => ['name' => 'Name', 'email' => 'Email', 'phone' => 'Telephone', 'subject' => 'Subject', 'message' => 'Message'],
    'registration' => ['name' => 'Name', 'address' => 'Full address', 'city' => 'City', 'locality' => 'Locality', 'email' => 'Email', 'phone' => 'Telephone', 'inquiry_nature' => 'Nature of inquiry', 'consular_help' => 'Consular help requested'],
    'contribution' => ['category' => 'Category', 'message' => 'Message', 'name' => 'Name', 'email' => 'Email', 'phone' => 'Telephone'],
};
$base = site_url("admin/{$inbox}/{$type}/{$row['id']}");
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= esc($row['reference']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<p><a href="<?= esc(site_url('admin/' . $inbox) . '?type=' . $type, 'attr') ?>">Back to the inbox</a></p>
<h1 class="page-title"><?= esc($definition['label']) ?> <?= esc($row['reference']) ?></h1>
<p class="text-muted">Received <?= esc((string) $row['created_at']) ?> UTC · Status: <strong><?= esc($statuses[$row['status']] ?? $row['status']) ?></strong>
    · Assigned to: <strong><?= esc($row['assigned_to'] !== null ? ($staff[(int) $row['assigned_to']] ?? 'unknown') : 'nobody') ?></strong></p>

<div class="row g-4">
    <div class="col-lg-7">
        <dl class="submission-fields" data-testid="submission-fields">
            <?php foreach ($fields as $key => $label): ?>
            <dt><?= esc($label) ?></dt>
            <dd><?php
                $value = (string) ($row[$key] ?? '');
                if ($key === 'category') {
                    $value = $categories[$value] ?? $value;
                }
                echo $value === '' ? '<span class="text-muted">Not given</span>' : nl2br(esc($value));
            ?></dd>
            <?php endforeach ?>
            <?php if ($type === 'registration'): ?>
            <dt>Country of residence</dt><dd><?= esc((string) ($country['name'] ?? '')) ?></dd>
            <dt>Mission chosen by the visitor</dt>
            <dd><?= $mission !== null ? esc($mission['name']) . ' (' . esc((string) $row['mission_chosen_at']) . ' UTC)' : '<span class="text-muted">None chosen</span>' ?></dd>
            <?php endif ?>
        </dl>
        <p class="small text-muted">Registration details are not sent to any mission automatically.</p>
    </div>
    <div class="col-lg-5">
        <?php if ($canManage): ?>
        <form method="post" action="<?= esc($base . '/status', 'attr') ?>" class="mb-3">
            <?= csrf_field() ?>
            <label class="form-label" for="status">Status</label>
            <div class="input-group">
                <select class="form-select" id="status" name="status">
                    <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= esc($key, 'attr') ?>"<?= $key === $row['status'] ? ' selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Change</button>
            </div>
        </form>
        <form method="post" action="<?= esc($base . '/assign', 'attr') ?>" class="mb-3">
            <?= csrf_field() ?>
            <label class="form-label" for="assigned_to">Responsible staff member</label>
            <div class="input-group">
                <select class="form-select" id="assigned_to" name="assigned_to">
                    <option value="">Nobody</option>
                    <?php foreach ($assignees as $id => $label): ?>
                    <option value="<?= (int) $id ?>"<?= (int) $id === (int) $row['assigned_to'] ? ' selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                </select>
                <button class="btn btn-outline-primary" type="submit">Assign</button>
            </div>
        </form>
        <?php endif ?>

        <h2 class="h5">Staff notes</h2>
        <p class="small text-muted">Only staff who can open this inbox see notes. They are never shown publicly or sent by email.</p>
        <?php if ($notes === []): ?>
        <p class="section-pending">No notes yet.</p>
        <?php else: ?>
        <ul class="list-unstyled" data-testid="notes">
            <?php foreach ($notes as $note): ?>
            <li class="mb-2 border-start ps-2"><div class="small text-muted"><?= esc((string) $note['username']) ?>, <?= esc((string) $note['created_at']) ?> UTC</div><?= nl2br(esc($note['note'])) ?></li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>
        <?php if ($canManage): ?>
        <form method="post" action="<?= esc($base . '/notes', 'attr') ?>">
            <?= csrf_field() ?>
            <label class="form-label" for="note">Add a note</label>
            <textarea class="form-control mb-2" id="note" name="note" rows="3" maxlength="5000" required></textarea>
            <button class="btn btn-outline-primary" type="submit">Add note</button>
        </form>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
