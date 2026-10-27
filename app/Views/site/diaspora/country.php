<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($page['title']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.byCountry'), locale_url('diaspora/countries')], [$page['title']]]]) ?>
    <article class="content-page">
        <h1 class="page-title"><?= esc($page['title']) ?></h1>
        <?php if (! empty($page['summary'])): ?><p class="lead"><?= esc($page['summary']) ?></p><?php endif ?>
        <?php if ($bodyHtml !== ''): ?><div class="prose"><?= $bodyHtml ?></div><?php endif ?>
        <?= view('site/partials/media', ['media' => $media]) ?>
    </article>
    <section aria-labelledby="country-missions">
        <h2 id="country-missions" class="section-heading"><?= esc(lang('Forms.missionsServing', [(string) ($country['name'] ?? '')])) ?></h2>
        <?= view('site/missions/_mission_list', ['missions' => $missions, 'showCoverage' => true]) ?>
    </section>
</div>
<?= $this->endSection() ?>
