<?php
/**
 * Missions as a grid of flag cards with a country search (MIS-01).
 *
 * The country name is always written out, so the flag is decorative and has
 * empty alt text: no one has to recognise a flag to find a mission (WCAG
 * 1.1.1, 1.4.1). The search box is hidden until assets/js/mission-filter.js
 * runs; without JavaScript the full list is shown.
 *
 * @var list<array<string, mixed>> $missions
 * @var array<int, list<string>>   $accreditations mission id => country names
 */
$accreditations ??= [];
?>
<?php if ($missions === []): ?>
    <?= view('site/partials/empty') ?>
<?php else: ?>
<div class="mission-search" data-mission-filter hidden>
    <label class="form-label" for="mission-search-input"><?= esc(lang('Public.searchByCountry')) ?></label>
    <input class="form-control" id="mission-search-input" type="search" autocomplete="off" aria-describedby="mission-search-hint mission-search-status">
    <p class="form-text" id="mission-search-hint"><?= esc(lang('Public.searchByCountryHint')) ?></p>
    <p class="form-text" id="mission-search-status" role="status" data-template="<?= esc(lang('Public.searchShowing', ['__SHOWN__', '__TOTAL__']), 'attr') ?>"></p>
    <p class="section-pending" data-mission-filter-empty hidden><?= esc(lang('Public.searchNoMatch')) ?></p>
</div>
<ul class="mission-cards" data-testid="mission-cards">
    <?php foreach ($missions as $mission): ?>
    <?php
    $abroad   = str_starts_with((string) $mission['category'], 'abroad_');
    $country  = (string) ($abroad ? ($mission['location_country'] ?? '') : ($mission['represents_country'] ?? ''));
    $flag     = flag_url(mission_flag_iso($mission));
    $covers   = $accreditations[(int) $mission['id']] ?? [];
    $haystack = mb_strtolower(implode(' ', [$country, $mission['name'], $mission['city'] ?? '', ...$covers]));
    ?>
    <li class="mission-card-item" data-search="<?= esc($haystack, 'attr') ?>">
        <a class="mission-flag-card" href="<?= esc(mission_url($mission), 'attr') ?>">
            <?php if ($flag !== null): ?>
            <img class="flag" src="<?= esc($flag, 'attr') ?>" alt="" width="96" height="72" loading="lazy">
            <?php else: ?>
            <span class="flag flag-missing" aria-hidden="true"></span>
            <?php endif ?>
            <span class="mission-flag-card-country"><?= esc($country !== '' ? $country : $mission['name']) ?></span>
            <?php if ($country !== ''): ?><span class="mission-flag-card-name"><?= esc($mission['name']) ?></span><?php endif ?>
        </a>
        <?php if ($covers !== []): ?>
        <p class="mission-flag-card-covers"><?= esc(lang('Public.alsoAccredited', [implode(', ', $covers)])) ?></p>
        <?php endif ?>
    </li>
    <?php endforeach ?>
</ul>
<?php endif ?>
