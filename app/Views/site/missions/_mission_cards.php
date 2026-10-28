<?php
/**
 * Missions as a grid of flag cards with a country search (MIS-01), laid out
 * like the reference embassies directory the sponsor chose: a search box,
 * then flag and country name cards in alphabetical order.
 *
 * The country name is always written out, so the flag is decorative and has
 * empty alt text: no one has to recognise a flag to find a mission (WCAG
 * 1.1.1, 1.4.1). The search box is hidden until assets/js/mission-filter.js
 * runs; without JavaScript the full list is shown. A search also finds the
 * mission that covers a country from elsewhere, and the card then says so.
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
    <label class="visually-hidden" for="mission-search-input"><?= esc(lang('Public.searchByCountry')) ?></label>
    <div class="mission-search-box">
        <svg class="mission-search-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="28" height="28"><circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2.2"/><path d="m15.5 15.5 5 5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>
        <input class="mission-search-input" id="mission-search-input" type="search" autocomplete="off" placeholder="<?= esc(lang('Public.searchByCountry'), 'attr') ?>" aria-describedby="mission-search-hint mission-search-status">
    </div>
    <p class="visually-hidden" id="mission-search-hint"><?= esc(lang('Public.searchByCountryHint')) ?></p>
    <p class="mission-search-status" id="mission-search-status" role="status" data-template="<?= esc(lang('Public.searchShowing', ['__SHOWN__', '__TOTAL__']), 'attr') ?>"></p>
    <p class="section-pending" data-mission-filter-empty hidden><?= esc(lang('Public.searchNoMatch')) ?></p>
</div>
<ul class="mission-cards" data-testid="mission-cards">
    <?php foreach ($missions as $mission): ?>
    <?php
    $abroad  = str_starts_with((string) $mission['category'], 'abroad_');
    $country = (string) ($abroad ? ($mission['location_country'] ?? '') : ($mission['represents_country'] ?? ''));
    $flag    = flag_url(mission_flag_iso($mission));
    $covers  = $accreditations[(int) $mission['id']] ?? [];
    ?>
    <li class="mission-card-item" data-search="<?= esc(mb_strtolower(implode(' ', [$country, $mission['name'], $mission['city'] ?? ''])), 'attr') ?>" data-covers="<?= esc(mb_strtolower(implode(' ', $covers)), 'attr') ?>">
        <a class="mission-flag-card" href="<?= esc(mission_url($mission), 'attr') ?>">
            <?php if ($flag !== null): ?>
            <img class="flag" src="<?= esc($flag, 'attr') ?>" alt="" width="64" height="48" loading="lazy">
            <?php else: ?>
            <span class="flag flag-missing" aria-hidden="true"></span>
            <?php endif ?>
            <span class="mission-flag-card-text">
                <span class="mission-flag-card-country"><?= esc($country !== '' ? $country : $mission['name']) ?></span>
                <?php if ($country !== ''): ?><span class="visually-hidden">: <?= esc($mission['name']) ?></span><?php endif ?>
                <?php if ($covers !== []): ?>
                <span class="mission-flag-card-covers" data-covers-note hidden><?= esc(lang('Public.alsoAccredited', [implode(', ', $covers)])) ?></span>
                <?php endif ?>
            </span>
        </a>
    </li>
    <?php endforeach ?>
</ul>
<?php endif ?>
