<?php
/**
 * Home page, laid out like the sponsor's reference ministry home page. Every item
 * comes from content editors publish; empty areas say so.
 *
 * @var array<string, mixed>|null           $lead
 * @var list<array<string, mixed>>          $statements
 * @var list<array<string, mixed>>          $news
 * @var array<int, array<string, mixed>>    $images
 * @var list<array<string, mixed>>          $engagements
 * @var list<array<string, mixed>>          $missionActivities
 * @var list<array<string, mixed>>          $quickLinks
 * @var list<array<string, mixed>>          $socialAccounts
 */
use App\Libraries\MediaService;

$photo = static function (array $article, string $class) use ($images): string {
    $image = $images[(int) $article['id']] ?? null;
    if ($image === null) {
        return '<div class="' . $class . ' home-photo-none" aria-hidden="true"></div>';
    }

    return '<img class="' . $class . '" src="' . esc(MediaService::url($image), 'attr') . '" alt="" loading="lazy">';
};
$date = static fn (array $article): string => '<time class="home-date" datetime="' . esc(substr((string) $article['published_at'], 0, 10), 'attr') . '">' . esc(format_date($article['published_at'])) . '</time>';

$tiles = [
    ['path' => 'about-south-sudan', 'key' => 'AboutSouthSudan'],
    ['path' => 'diaspora', 'key' => 'Diaspora'],
    ['path' => 'services', 'key' => 'Services'],
    ['path' => 'missions/in-south-sudan', 'key' => 'ForeignMissions'],
];
?>
<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc(lang('Site.nav.home')) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="home">
    <h1 class="visually-hidden"><?= esc(lang('Site.ministryName')) ?></h1>

    <?php if ($emergencyHtml !== ''): ?>
    <section class="home-emergency" aria-labelledby="home-emergency" data-testid="emergency-contacts">
        <div class="container-xl home-emergency-inner">
            <h2 id="home-emergency" class="home-emergency-tag"><?= esc(lang('Public.emergencyContacts')) ?></h2>
            <div class="prose"><?= $emergencyHtml ?></div>
        </div>
    </section>
    <?php endif ?>

    <section class="home-section" aria-labelledby="home-featured" data-testid="featured-news">
        <div class="container-xl">
            <?php // Visible heading removed at the sponsor's request; the space stays blank. ?>
            <div class="home-head home-head-blank">
                <h2 id="home-featured" class="visually-hidden"><?= esc(lang('Public.featuredNews')) ?></h2>
            </div>
            <div class="home-lead-grid">
                <?php if ($lead === null): ?>
                    <?= view('site/partials/empty') ?>
                <?php else: ?>
                <article class="home-lead">
                    <?= $photo($lead, 'home-lead-photo') ?>
                    <div class="home-lead-text">
                        <?php if ((int) ($lead['is_featured'] ?? 0) === 1): ?><p class="home-kicker"><?= esc(lang('Public.featured')) ?></p><?php endif ?>
                        <h3><a class="stretched-link" href="<?= esc(article_url($lead), 'attr') ?>"><?= esc($lead['title']) ?></a></h3>
                        <?= $date($lead) ?>
                    </div>
                </article>
                <?php endif ?>

                <section class="home-finder" aria-labelledby="home-finder" data-testid="mission-finder">
                    <h3 id="home-finder" class="section-heading"><?= esc(lang('Public.missionFinder')) ?></h3>
                    <?= view('site/partials/finder', ['countries' => $finderCountries]) ?>
                </section>

                <div class="home-latest" data-testid="latest-statements">
                    <h3 class="home-side-title"><?= esc(lang('Public.latestStatements')) ?></h3>
                    <?php if ($statements === []): ?>
                        <?= view('site/partials/empty') ?>
                    <?php else: ?>
                    <ul class="home-statements">
                        <?php foreach ($statements as $article): ?>
                        <li><?= $date($article) ?><a href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></li>
                        <?php endforeach ?>
                    </ul>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container-xl home-tiles-row">
        <nav class="home-tiles" aria-label="<?= esc(lang('Public.featuredPages'), 'attr') ?>">
            <?php foreach ($tiles as $tile): ?>
            <a class="home-tile" href="<?= esc(locale_url($tile['path']), 'attr') ?>">
                <strong><?= esc(lang('Public.tile' . $tile['key'])) ?></strong>
                <span><?= esc(lang('Public.tile' . $tile['key'] . 'Text')) ?></span>
            </a>
            <?php endforeach ?>
        </nav>
    </div>

    <div class="container-xl home-section home-split">
        <section aria-labelledby="home-engagements" data-testid="minister-engagements">
            <div class="home-head">
                <h2 id="home-engagements"><?= esc(lang('Public.ministerEngagements')) ?></h2>
                <?php if ($engagements !== []): ?>
                <a class="home-more" href="<?= esc(locale_url('news-press/tags/' . config('ContentTypes')->ministerEngagementTag), 'attr') ?>"><?= esc(lang('Public.viewAll')) ?></a>
                <?php endif ?>
            </div>
            <?php if ($engagements === []): ?>
                <?= view('site/partials/empty') ?>
            <?php else: ?>
            <ul class="home-engagements">
                <?php foreach ($engagements as $article): ?>
                <?php $time = \CodeIgniter\I18n\Time::parse((string) $article['published_at'], 'UTC'); ?>
                <li>
                    <time class="home-cal" datetime="<?= esc($time->format('Y-m-d'), 'attr') ?>"><b><?= esc($time->toLocalizedString('d')) ?></b> <small><?= esc($time->toLocalizedString('MMM')) ?></small></time>
                    <a href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a>
                </li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
        </section>

        <section class="home-social" aria-labelledby="home-social" data-testid="social-media">
            <h2 id="home-social"><?= esc(lang('Public.socialOnHome')) ?></h2>
            <?php if ($socialAccounts === []): ?>
            <p class="section-pending"><?= esc(lang('Public.socialPending')) ?></p>
            <?php else: ?>
            <ul class="home-social-list">
                <?php foreach ($socialAccounts as $account): ?>
                <li><a href="<?= esc($account['url'], 'attr') ?>" rel="noopener"><span><?= esc($account['platform']) ?></span> <?= esc($account['label']) ?></a></li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
            <a class="home-more" href="<?= esc(locale_url('news-press/social-media'), 'attr') ?>"><?= esc(lang('Public.socialMedia')) ?></a>
        </section>
    </div>

    <section class="home-section home-band" aria-labelledby="home-news" data-testid="current-affairs">
        <div class="container-xl">
            <div class="home-head">
                <h2 id="home-news"><?= esc(lang('Public.currentAffairs')) ?></h2>
                <a class="home-more" href="<?= esc(locale_url('news-press/news'), 'attr') ?>"><?= esc(lang('Public.moreNews')) ?></a>
            </div>
            <?php if ($news === []): ?>
                <?= view('site/partials/empty') ?>
            <?php else: ?>
            <ul class="home-cards">
                <?php foreach ($news as $article): ?>
                <li class="home-card">
                    <?= $photo($article, 'home-card-photo') ?>
                    <?= $date($article) ?>
                    <h3><a class="stretched-link" href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></h3>
                </li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
        </div>
    </section>

    <section class="home-section" aria-labelledby="home-missions" data-testid="mission-activities">
        <div class="container-xl">
            <div class="home-head">
                <h2 id="home-missions"><?= esc(lang('Public.missionActivitiesHome')) ?></h2>
                <?php if ($missionActivities !== []): ?>
                <a class="home-more" href="<?= esc(locale_url('news-press/mission-activities'), 'attr') ?>"><?= esc(lang('Public.moreActivities')) ?></a>
                <?php endif ?>
            </div>
            <?php if ($missionActivities === []): ?>
                <?= view('site/partials/empty') ?>
            <?php else: ?>
            <ul class="home-cards">
                <?php foreach ($missionActivities as $article): ?>
                <li class="home-card">
                    <?= $photo($article, 'home-card-photo') ?>
                    <?php if (! empty($article['mission_name'])): ?><p class="home-card-where"><?= esc($article['mission_name']) ?></p><?php endif ?>
                    <h3><a class="stretched-link" href="<?= esc(article_url($article), 'attr') ?>"><?= esc($article['title']) ?></a></h3>
                    <?= $date($article) ?>
                </li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
        </div>
    </section>

    <section class="container-xl home-section" aria-labelledby="home-links" data-testid="quick-links">
        <div class="home-head"><h2 id="home-links"><?= esc(lang('Public.quickLinks')) ?></h2></div>
        <?php if ($quickLinks === []): ?>
            <?= view('site/partials/empty') ?>
        <?php else: ?>
        <ul class="home-links">
            <?php foreach ($quickLinks as $link): ?>
            <li><a href="<?= esc($link['url'], 'attr') ?>"><?= esc($link['label']) ?></a></li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
