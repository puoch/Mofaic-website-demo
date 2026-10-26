<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= esc(lang('Admin.dashboard')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title"><?= esc(lang('Admin.dashboard')) ?></h1>
<p><?= esc(lang('Admin.signedInAs', [$user->email])) ?></p>

<div class="row g-4">
    <section class="col-md-4">
        <h2 class="h5"><?= esc(lang('Admin.yourRoles')) ?></h2>
        <ul data-testid="roles">
            <?php foreach ($roles as $role): ?>
            <li><?= esc($role) ?></li>
            <?php endforeach ?>
        </ul>
    </section>
    <section class="col-md-4">
        <h2 class="h5"><?= esc(lang('Admin.yourDepartments')) ?></h2>
        <?php if ($departments === []): ?>
        <p class="section-pending"><?= esc(lang('Admin.noDepartments')) ?></p>
        <?php else: ?>
        <ul>
            <?php foreach ($departments as $department): ?>
            <li><?= esc($department['name']) ?></li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>
    </section>
    <section class="col-md-4">
        <h2 class="h5"><?= esc(lang('Admin.yourPermissions')) ?></h2>
        <ul data-testid="permissions">
            <?php foreach ($permissions as $description): ?>
            <li><?= esc($description) ?></li>
            <?php endforeach ?>
        </ul>
    </section>
</div>
<?= $this->endSection() ?>
