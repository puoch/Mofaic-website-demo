<?= $this->extend('layouts/public') ?>

<?= $this->section('title') ?><?= esc($heading) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-xl page-body">
    <?= view('site/partials/breadcrumb', ['crumbs' => [[lang('Site.nav.newsPress'), locale_url('news-press')], [$heading]]]) ?>
    <h1 class="page-title"><?= esc($heading) ?></h1>

    <form class="filter-form" method="get" action="<?= esc(current_url(), 'attr') ?>" role="search">
        <div class="filter-row">
            <div>
                <label class="form-label" for="filter-q"><?= esc(lang('Public.keywords')) ?></label>
                <input class="form-control" type="search" id="filter-q" name="q" value="<?= esc($filters['q'], 'attr') ?>" maxlength="100">
            </div>
            <div>
                <label class="form-label" for="filter-from"><?= esc(lang('Public.fromDate')) ?></label>
                <input class="form-control" type="date" id="filter-from" name="from" value="<?= esc($filters['from'], 'attr') ?>">
            </div>
            <div>
                <label class="form-label" for="filter-to"><?= esc(lang('Public.toDate')) ?></label>
                <input class="form-control" type="date" id="filter-to" name="to" value="<?= esc($filters['to'], 'attr') ?>">
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit"><?= esc(lang('Public.applyFilters')) ?></button>
                <a class="btn btn-link" href="<?= esc(current_url(), 'attr') ?>"><?= esc(lang('Public.clearFilters')) ?></a>
            </div>
        </div>
    </form>

    <p class="result-count" role="status" data-testid="result-count"><?= esc(lang('Public.resultCount', [$total])) ?></p>
    <?= view('site/partials/article_list', ['articles' => $articles]) ?>
    <?= view('site/partials/pagination', ['page' => $page, 'pages' => $pages, 'query' => $filters]) ?>
</div>
<?= $this->endSection() ?>
