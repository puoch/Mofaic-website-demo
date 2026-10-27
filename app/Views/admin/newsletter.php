<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Diaspora newsletter<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title">Diaspora newsletter</h1>
<p data-testid="subscriber-counts">Confirmed subscribers: <strong><?= (int) $counts['confirmed'] ?></strong>
    · Waiting for confirmation: <?= (int) $counts['pending'] ?> · Unsubscribed: <?= (int) $counts['unsubscribed'] ?></p>
<p class="small text-muted">Write an issue as a news article in the "Diaspora newsletter" category, in a diaspora department, and publish it. It can then be sent once.
    Email is sent by the server's scheduled <code>mail:send</code> task.</p>

<?php if ($issues === []): ?>
<p class="section-pending">No newsletter issues yet.</p>
<?php else: ?>
<table class="table table-sm align-middle" data-testid="issues">
    <thead><tr><th scope="col">Issue</th><th scope="col">Status</th><th scope="col">Sent</th><th scope="col"></th></tr></thead>
    <tbody>
        <?php foreach ($issues as $issue): ?>
        <tr>
            <th scope="row"><a href="<?= esc(site_url('admin/content/articles/' . $issue['id']), 'attr') ?>"><?= esc($issue['title']) ?></a></th>
            <td><?= esc($issue['status']) ?></td>
            <td><?= $issue['sent_at'] !== null ? esc((string) $issue['sent_at']) . ' UTC to ' . (int) $issue['queued_count'] : 'Not sent' ?></td>
            <td>
                <?php if ($issue['can_send']): ?>
                <form method="post" action="<?= esc(site_url('admin/newsletter/' . $issue['id'] . '/send'), 'attr') ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-primary" type="submit">Send to subscribers</button>
                </form>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php endif ?>
<?= $this->endSection() ?>
