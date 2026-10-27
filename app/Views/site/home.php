<?php
use App\Libraries\MediaService;

?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.nav.home')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body home">
    <h1 class="visually-hidden"><?= esc(lang('Site.ministryName')) ?></h1>

    <?php if ($emergencyHtml !== ''): ?>
    <section class="home-emergency" aria-labelledby="home-emergency" data-testid="emergency-contacts">
        <h2 id="home-emergency" class="h5"><?= esc(lang('Public.emergencyContacts')) ?></h2>
        <div class="prose"><?= $emergencyHtml ?></div>
    </section>
    <?php endif ?>

    <div class="home-grid">
        <section class="home-featured" aria-labelledby="home-featured" data-testid="featured-news">
            <h2 id="home-featured" class="section-heading"><?= esc(lang('Public.featuredNews')) ?></h2>
            <?php if ($featured === []): ?>
                <?= view('site/partials/empty') ?>
            <?php else: ?>
            <div class="featured-list">
                <?php foreach ($featured as $article): ?>
                <article class="featured-item">
                    <?php if (isset($featuredImages[(int) $article['id']])): ?>
                    <?php $image = $featuredImages[(int) $article['id']]; ?>
                    <img class="featured-image" src="<?= esc(MediaService::url($image), 'attr') ?>" alt="<?= esc((string) $image['alt_text'], 'attr') ?>" loading="lazy">
                    <?php endif ?>
                    <h3 class="article-title"><a href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></h3>
                    <p class="article-date"><?= esc(format_date($article['published_at'])) ?></p>
                    <?php if (! empty($article['summary'])): ?><p class="article-summary"><?= esc($article['summary']) ?></p><?php endif ?>
                </article>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </section>

        <section class="home-finder" aria-labelledby="home-finder" data-testid="mission-finder">
            <h2 id="home-finder" class="section-heading"><?= esc(lang('Public.missionFinder')) ?></h2>
            <?= view('site/partials/finder', ['countries' => $finderCountries]) ?>
        </section>
    </div>

    <div class="home-columns">
        <section aria-labelledby="home-engagements" data-testid="minister-engagements">
            <h2 id="home-engagements" class="section-heading"><?= esc(lang('Public.ministerEngagements')) ?></h2>
            <?= view('site/partials/article_list', ['articles' => $engagements]) ?>
            <?php if ($engagements !== []): ?>
            <p><a href="<?= esc(locale_url('news-press/tags/' . config('ContentTypes')->ministerEngagementTag), 'attr') ?>"><?= esc(lang('Public.viewAll')) ?></a></p>
            <?php endif ?>
        </section>

        <section aria-labelledby="home-missions" data-testid="mission-activities">
            <h2 id="home-missions" class="section-heading"><?= esc(lang('Public.missionActivitiesHome')) ?></h2>
            <?= view('site/partials/article_list', ['articles' => $missionActivities]) ?>
            <?php if ($missionActivities !== []): ?>
            <p><a href="<?= esc(locale_url('news-press/mission-activities'), 'attr') ?>"><?= esc(lang('Public.viewAll')) ?></a></p>
            <?php endif ?>
        </section>

        <section aria-labelledby="home-links" data-testid="quick-links">
            <h2 id="home-links" class="section-heading"><?= esc(lang('Public.quickLinks')) ?></h2>
            <?php if ($quickLinks === []): ?>
                <?= view('site/partials/empty') ?>
            <?php else: ?>
            <ul class="link-list">
                <?php foreach ($quickLinks as $link): ?>
                <li><a href="<?= esc($link['url'], 'attr') ?>"><?= esc($link['label']) ?></a></li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
