<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Diaspora inbox access<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title">Diaspora inbox access</h1>
<p>Diaspora registrations and contributions contain personal data. No role gives access to them, including Super Administrator.
    Grant access to named central Ministry administrators or members of a diaspora department. Every change is recorded in the audit log.</p>

<h2 class="h5">Staff with access</h2>
<?php if ($holders === []): ?>
<p class="section-pending" data-testid="no-holders">Nobody has access yet.</p>
<?php else: ?>
<table class="table table-sm align-middle" data-testid="holders">
    <thead><tr><th scope="col">Staff member</th><th scope="col">Access</th><th scope="col"></th></tr></thead>
    <tbody>
        <?php foreach ($holders as $id => $level): ?>
        <tr>
            <th scope="row"><?= esc($labels[$id] ?? ('User ' . $id)) ?></th>
            <td><?= $level === 'manage' ? 'View and handle' : 'View only' ?></td>
            <td>
                <form method="post" action="<?= esc(site_url('admin/diaspora-access/' . $id . '/remove'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove<span class="visually-hidden"> access for <?= esc($labels[$id] ?? '') ?></span></button>
                </form>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>

<h2 class="h5 mt-4">Grant access</h2>
<?php if ($eligible === []): ?>
<p class="section-pending">No other eligible staff member is without access.</p>
<?php else: ?>
<form method="post" action="<?= esc(site_url('admin/diaspora-access'), 'attr') ?>" class="row g-2 align-items-end">
    <?= csrf_field() ?>
    <div class="col-md-5">
        <label class="form-label" for="user_id">Staff member</label>
        <select class="form-select" id="user_id" name="user_id" required>
            <?php foreach ($eligible as $id => $label): ?>
            <option value="<?= (int) $id ?>"><?= esc($label) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="level">Access</label>
        <select class="form-select" id="level" name="level">
            <option value="view">View only</option>
            <option value="manage">View and handle (status, assignment, notes)</option>
        </select>
    </div>
    <div class="col-md-3"><button class="btn btn-primary" type="submit">Grant</button></div>
</form>
<?php endif ?>
<?= $this->endSection() ?>
