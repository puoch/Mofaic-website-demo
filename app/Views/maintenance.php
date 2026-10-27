<?php
/**
 * Setup page for hosts without SSH (Controllers\Maintenance).
 *
 * @var CodeIgniter\View\View                                                       $this
 * @var string                                                                      $token
 * @var string|null                                                                 $message
 * @var string|null                                                                 $error
 * @var bool                                                                        $unlocked
 * @var int                                                                         $pending
 * @var int                                                                         $staff
 * @var array<string, array{title: string}>                                         $groups
 * @var list<array{area: string, check: string, status: string, detail: string}>    $checks
 */
?>
<?= $this->extend('auth/layout') ?>

<?= $this->section('title') ?>Maintenance<?= $this->endSection() ?>

<?= $this->section('main') ?>
<div class="py-4 col-lg-9">
    <h1 class="h3">Maintenance</h1>
    <p>For hosting without a command line. Remove <code>hosting.maintenanceToken</code> from <code>.env</code> when you have finished; the page then disappears.</p>

    <?php if ($error !== null): ?>
    <div class="alert alert-danger" role="alert"><?= esc($error) ?></div>
    <?php endif ?>
    <?php if ($message !== null): ?>
    <div class="alert alert-info" role="status"><?= esc($message) ?></div>
    <?php endif ?>

    <?php if (! $unlocked): ?>
    <form method="post" action="<?= esc(site_url('maintenance'), 'attr') ?>">
        <?= csrf_field() ?>
        <label class="form-label" for="token">Maintenance token</label>
        <input class="form-control mb-3" type="password" id="token" name="token" autocomplete="off" required>
        <button class="btn btn-primary" type="submit">Continue</button>
    </form>
    <?php else: ?>

    <h2 class="h5 mt-4">Database</h2>
    <p><?= $pending === 0 ? 'All migrations have run.' : esc($pending) . ' migration(s) have not run yet.' ?></p>
    <form method="post" action="<?= esc(site_url('maintenance'), 'attr') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
        <input type="hidden" name="action" value="migrate">
        <button class="btn btn-primary" type="submit">Run migrations and add structure pages</button>
    </form>

    <?php if ($pending === 0): ?>
    <h2 class="h5 mt-4">Create a staff account</h2>
    <p><?= esc($staff) ?> staff account(s) now. Each new account signs in with an emailed code, so email must work first.</p>
    <form method="post" action="<?= esc(site_url('maintenance'), 'attr') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= esc($token, 'attr') ?>">
        <input type="hidden" name="action" value="create_user">
        <label class="form-label" for="username">Username</label>
        <input class="form-control mb-2" id="username" name="username" autocomplete="off" required>
        <label class="form-label" for="email">Email</label>
        <input class="form-control mb-2" type="email" id="email" name="email" autocomplete="off" required>
        <label class="form-label" for="password">Password (at least 12 characters)</label>
        <input class="form-control mb-2" type="password" id="password" name="password" autocomplete="new-password" required minlength="12">
        <label class="form-label" for="group">Role</label>
        <select class="form-select mb-3" id="group" name="group" required>
            <?php foreach ($groups as $key => $group): ?>
            <option value="<?= esc($key, 'attr') ?>"><?= esc($group['title']) ?></option>
            <?php endforeach ?>
        </select>
        <button class="btn btn-primary" type="submit">Create account</button>
    </form>

    <h2 class="h5 mt-4">Go-live check</h2>
    <p>The same list as <code>php spark golive:check</code>. A test copy is not meant to pass it.</p>
    <table class="table table-sm">
        <thead><tr><th scope="col">Result</th><th scope="col">Check</th><th scope="col">Detail</th></tr></thead>
        <tbody>
        <?php foreach ($checks as $row): ?>
            <tr>
                <td><?= esc(strtoupper($row['status'])) ?></td>
                <td><?= esc($row['area'] . ': ' . $row['check']) ?></td>
                <td><?= esc($row['detail']) ?></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
