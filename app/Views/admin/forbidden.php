<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?>Not allowed<?= $this->endSection() ?>

<?= $this->section('content') ?>
<h1 class="page-title">You can't make this change</h1>
<p>Your role or department membership doesn't cover this item. Ask a Ministry Administrator if you need access.</p>
<p><a href="<?= esc(site_url('admin/publishing'), 'attr') ?>">Back to publishing</a></p>
<?= $this->endSection() ?>
