<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.nav.diaspora')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.diaspora')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Site.nav.diaspora')) ?></h1>
    <p class="lead"><?= esc(lang('Forms.diasporaIntro')) ?></p>

    <ul class="section-cards" data-testid="diaspora-areas">
        <?php foreach ([
            ['diaspora/register', 'Forms.register', 'Forms.registerIntro'],
            ['diaspora/contribute', 'Forms.contribute', 'Forms.contributeIntro'],
            ['diaspora/stay-informed', 'Forms.stayInformed', 'Forms.stayInformedIntro'],
            ['diaspora/countries', 'Forms.byCountry', 'Forms.byCountryIntro'],
        ] as [$path, $title, $intro]): ?>
        <li class="section-card">
            <h2 class="h5"><a href="<?= esc(locale_url($path), 'attr') ?>"><?= esc(lang($title)) ?></a></h2>
            <p class="mb-0"><?= esc(lang($intro)) ?></p>
        </li>
        <?php endforeach ?>
    </ul>

    <section aria-labelledby="diaspora-latest">
        <h2 id="diaspora-latest" class="section-heading"><?= esc(lang('Forms.stayInformed')) ?></h2>
        <?= view('site/partials/article_list', ['articles' => $latest]) ?>
    </section>
</div>
<?= $this->endSection() ?>
