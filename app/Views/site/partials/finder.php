<?php
/**
 * Country-based Diplomatic Mission Finder. Works without JavaScript.
 *
 * @var list<array<string, mixed>> $countries
 */
?>
<?php if ($countries === []): ?>
<p class="section-pending"><?= esc(lang('Public.noCountriesYet')) ?></p>
<?php else: ?>
<form class="finder-form" method="get" action="<?= esc(locale_url('missions/finder'), 'attr') ?>">
    <p><?= esc(lang('Public.missionFinderIntro')) ?></p>
    <label class="form-label" for="finder-country"><?= esc(lang('Public.country')) ?></label>
    <div class="finder-row">
        <select class="form-select" id="finder-country" name="country" required>
            <option value=""><?= esc(lang('Public.chooseCountry')) ?></option>
            <?php foreach ($countries as $country): ?>
            <option value="<?= esc($country['iso_code'], 'attr') ?>"><?= esc($country['name']) ?></option>
            <?php endforeach ?>
        </select>
        <button class="btn btn-primary" type="submit"><?= esc(lang('Public.findMissions')) ?></button>
    </div>
</form>
<?php endif ?>
