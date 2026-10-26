<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= esc(lang('Admin.auditLog')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title"><?= esc(lang('Admin.auditLog')) ?></h1>
<?php if ($events === []): ?>
<p class="section-pending"><?= esc(lang('Admin.auditEmpty')) ?></p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm">
        <thead>
            <tr>
                <th scope="col"><?= esc(lang('Admin.when')) ?></th>
                <th scope="col"><?= esc(lang('Admin.event')) ?></th>
                <th scope="col"><?= esc(lang('Admin.actor')) ?></th>
                <th scope="col"><?= esc(lang('Admin.subject')) ?></th>
                <th scope="col"><?= esc(lang('Admin.ipAddress')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
            <tr>
                <td><?= esc($event['occurred_at']) ?></td>
                <td><code><?= esc($event['event']) ?></code></td>
                <td><?= esc($event['actor_user_id'] ?? '') ?></td>
                <td><?= esc(trim(($event['subject_type'] ?? '') . ' ' . ($event['subject_id'] ?? ''))) ?></td>
                <td><?= esc($event['ip_address'] ?? '') ?></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>
<?= $this->endSection() ?>
