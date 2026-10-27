<?php
/**
 * @var list<array{user: \CodeIgniter\Shield\Entities\User, role: string, departments: array<int, string>, diaspora: string|null, lastSignIn: string|null}> $rows
 * @var array<string, array{title: string, description: string}> $groups
 * @var array<int, string> $departments
 * @var array<string, string> $old
 * @var array<string, string> $errors
 */
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Staff accounts<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title">Staff accounts</h1>
<p>Everyone who can sign in to the administration area. Use this list for the quarterly access review. When someone leaves, ban the account the same day;
    accounts are never deleted. Every change is recorded in the audit log. Diaspora inbox access is granted on its own screen.</p>

<div class="table-responsive">
<table class="table table-sm align-middle" data-testid="staff-list">
    <caption class="visually-hidden">Staff accounts, <?= count($rows) ?> in total, listed on <?= esc(date('Y-m-d')) ?></caption>
    <thead><tr><th scope="col">Staff member</th><th scope="col">Role</th><th scope="col">Departments</th><th scope="col">Diaspora access</th><th scope="col">Status</th><th scope="col">Last sign-in</th></tr></thead>
    <tbody>
        <?php foreach ($rows as $row): ?>
        <?php $user = $row['user']; $id = (int) $user->id; $self = $id === $selfId; ?>
        <tr data-testid="staff-<?= $id ?>">
            <th scope="row"><?= esc($user->username) ?><br><span class="text-muted small"><?= esc($user->email ?? '') ?></span><?= $self ? ' <span class="badge text-bg-secondary">You</span>' : '' ?></th>
            <td>
                <?php if ($self): ?>
                <?= esc($groups[$row['role']]['title'] ?? $row['role']) ?>
                <?php else: ?>
                <form method="post" action="<?= esc(site_url('admin/staff/' . $id . '/role'), 'attr') ?>" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <label class="visually-hidden" for="group-<?= $id ?>">Role for <?= esc($user->username) ?></label>
                    <select class="form-select form-select-sm" id="group-<?= $id ?>" name="group">
                        <?php foreach ($groups as $key => $group): ?>
                        <option value="<?= esc($key, 'attr') ?>"<?= $key === $row['role'] ? ' selected' : '' ?>><?= esc($group['title']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Save<span class="visually-hidden"> role for <?= esc($user->username) ?></span></button>
                </form>
                <?php endif ?>
            </td>
            <td>
                <?php if ($row['departments'] === []): ?><span class="text-muted">None</span><?php endif ?>
                <ul class="list-unstyled mb-1">
                    <?php foreach ($row['departments'] as $departmentId => $name): ?>
                    <li>
                        <form method="post" action="<?= esc(site_url('admin/staff/' . $id . '/department'), 'attr') ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="department_id" value="<?= (int) $departmentId ?>">
                            <input type="hidden" name="action" value="remove">
                            <?= esc($name) ?>
                            <button class="btn btn-link btn-sm p-0 align-baseline" type="submit">Remove<span class="visually-hidden"> <?= esc($user->username) ?> from <?= esc($name) ?></span></button>
                        </form>
                    </li>
                    <?php endforeach ?>
                </ul>
                <?php $others = array_diff_key($departments, $row['departments']); ?>
                <?php if ($others !== []): ?>
                <form method="post" action="<?= esc(site_url('admin/staff/' . $id . '/department'), 'attr') ?>" class="d-flex gap-1">
                    <?= csrf_field() ?>
                    <label class="visually-hidden" for="department-<?= $id ?>">Add <?= esc($user->username) ?> to a department</label>
                    <select class="form-select form-select-sm" id="department-<?= $id ?>" name="department_id">
                        <?php foreach ($others as $departmentId => $name): ?>
                        <option value="<?= (int) $departmentId ?>"><?= esc($name) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Add<span class="visually-hidden"> department for <?= esc($user->username) ?></span></button>
                </form>
                <?php endif ?>
            </td>
            <td><?= $row['diaspora'] === null ? '<span class="text-muted">None</span>' : ($row['diaspora'] === 'manage' ? 'View and handle' : 'View only') ?></td>
            <td>
                <?php if ($user->isBanned()): ?>
                <span class="badge text-bg-danger">Banned</span>
                <form method="post" action="<?= esc(site_url('admin/staff/' . $id . '/unban'), 'attr') ?>" class="mt-1">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Allow sign-in<span class="visually-hidden"> for <?= esc($user->username) ?></span></button>
                </form>
                <?php else: ?>
                <span class="badge text-bg-success">Can sign in</span>
                <?php if (! $self): ?>
                <form method="post" action="<?= esc(site_url('admin/staff/' . $id . '/ban'), 'attr') ?>" class="d-flex gap-1 mt-1">
                    <?= csrf_field() ?>
                    <label class="visually-hidden" for="reason-<?= $id ?>">Reason for banning <?= esc($user->username) ?></label>
                    <input class="form-control form-control-sm" id="reason-<?= $id ?>" name="reason" maxlength="190" placeholder="Reason (optional)">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Ban<span class="visually-hidden"> <?= esc($user->username) ?></span></button>
                </form>
                <?php endif ?>
                <?php endif ?>
            </td>
            <td><?= esc($row['lastSignIn'] ?? 'Never') ?></td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
</div>

<h2 class="h5 mt-4" id="new-account">New staff account</h2>
<?php if ($errors !== []): ?>
<div class="alert alert-danger" role="alert" data-testid="create-errors">
    <p class="mb-1">The account was not created:</p>
    <ul class="mb-0"><?php foreach ($errors as $message): ?><li><?= esc($message) ?></li><?php endforeach ?></ul>
</div>
<?php endif ?>
<form method="post" action="<?= esc(site_url('admin/staff'), 'attr') ?>" class="row g-2 align-items-end" aria-labelledby="new-account">
    <?= csrf_field() ?>
    <div class="col-md-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" required maxlength="30" autocomplete="off" value="<?= esc($old['username'] ?? '', 'attr') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="email">Work email</label>
        <input class="form-control" id="email" name="email" type="email" required autocomplete="off" value="<?= esc($old['email'] ?? '', 'attr') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="password">First password</label>
        <input class="form-control" id="password" name="password" type="password" required minlength="12" autocomplete="new-password" aria-describedby="password-help">
    </div>
    <div class="col-md-3">
        <label class="form-label" for="group">Role</label>
        <select class="form-select" id="group" name="group">
            <?php foreach ($groups as $key => $group): ?>
            <option value="<?= esc($key, 'attr') ?>"<?= $key === ($old['group'] ?? 'readonly') ? ' selected' : '' ?>><?= esc($group['title']) ?></option>
            <?php endforeach ?>
        </select>
    </div>
    <p class="form-text col-12 mb-0" id="password-help">At least 12 characters. Give the password to the person in person or by phone, never in the same email as the username.</p>
    <div class="col-12"><button class="btn btn-primary" type="submit">Create account</button></div>
</form>
<?= $this->endSection() ?>
