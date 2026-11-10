<?php
/**
 * Site search results.
 *
 * @var string $q
 * @var array{articles: list<array<string, mixed>>, pages: list<array<string, mixed>>, missions: list<array<string, mixed>>} $results
 * @var int $total
 */
$sections = config(Config\ContentTypes::class)->pageSections;
$sectionLabels = [
    'about-mfaic'       => lang('Site.nav.aboutMfaic'),
    'about-south-sudan' => lang('Site.footerAboutSouthSudan'),
    'services'          => lang('Site.nav.services'),
];
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.search')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.search')]]]) ?>
    <h1 class="page-title"><?= esc(lang('Site.search')) ?></h1>

    <form class="filter-form" method="get" action="<?= esc(locale_url('search'), 'attr') ?>" role="search">
        <div class="filter-row">
            <div>
                <label class="form-label" for="search-q"><?= esc(lang('Site.searchLabel')) ?></label>
                <input class="form-control" type="search" id="search-q" name="q" value="<?= esc($q, 'attr') ?>" maxlength="100" required>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit"><?= esc(lang('Site.search')) ?></button>
            </div>
        </div>
    </form>

    <?php if ($q !== ''): ?>
    <p class="result-count" role="status" data-testid="result-count"><?= esc(lang('Public.resultCount', [$total])) ?></p>

    <?php if ($results['articles'] !== []): ?>
    <section class="search-group" aria-labelledby="search-articles">
        <h2 class="search-group-title" id="search-articles"><?= esc(lang('Site.nav.newsPress')) ?></h2>
        <ul class="search-results" data-testid="search-articles">
            <?php foreach ($results['articles'] as $article): ?>
            <li>
                <h3 class="search-result-title"><a href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></h3>
                <p class="search-result-meta"><time datetime="<?= esc(substr((string) $article['published_at'], 0, 10), 'attr') ?>"><?= esc(format_date($article['published_at'])) ?></time></p>
                <?php if (! empty($article['summary'])): ?>
                <p class="search-result-summary"><?= esc($article['summary']) ?></p>
                <?php endif ?>
            </li>
            <?php endforeach ?>
        </ul>
    </section>
    <?php endif ?>

    <?php if ($results['pages'] !== []): ?>
    <section class="search-group" aria-labelledby="search-pages">
        <h2 class="search-group-title" id="search-pages"><?= esc(lang('Public.searchPages')) ?></h2>
        <ul class="search-results" data-testid="search-pages">
            <?php foreach ($results['pages'] as $page): ?>
            <li>
                <h3 class="search-result-title"><a href="<?= esc(locale_url($page['section'] . '/' . $page['path']), 'attr') ?>"><?= esc($page['title']) ?></a></h3>
                <p class="search-result-meta"><?= esc($sectionLabels[$page['section']] ?? $sections[$page['section']] ?? $page['section']) ?></p>
                <?php if (! empty($page['summary'])): ?>
                <p class="search-result-summary"><?= esc($page['summary']) ?></p>
                <?php endif ?>
            </li>
            <?php endforeach ?>
        </ul>
    </section>
    <?php endif ?>

    <?php if ($results['missions'] !== []): ?>
    <section class="search-group" aria-labelledby="search-missions">
        <h2 class="search-group-title" id="search-missions"><?= esc(lang('Site.nav.missions')) ?></h2>
        <ul class="search-results" data-testid="search-missions">
            <?php foreach ($results['missions'] as $mission): ?>
            <li>
                <h3 class="search-result-title"><a href="<?= esc(mission_url($mission), 'attr') ?>"><?= esc($mission['name']) ?></a></h3>
                <?php $place = implode(', ', array_filter([$mission['city'] ?? null, $mission['location_name'] ?? null])); ?>
                <?php if ($place !== ''): ?>
                <p class="search-result-meta"><?= esc($place) ?></p>
                <?php endif ?>
            </li>
            <?php endforeach ?>
        </ul>
    </section>
    <?php endif ?>

    <?php if ($total === 0): ?>
    <p class="section-pending" data-testid="search-empty"><?= esc(lang('Public.searchNothingFound', [$q])) ?></p>
    <?php endif ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
