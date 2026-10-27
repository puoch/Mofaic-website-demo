<?php
/**
 * @var list<array<string, mixed>> $missions
 * @var bool                       $showCoverage rows carry a 'coverage' value
 */
$showCoverage ??= false;
?>
<?php if ($missions === []): ?>
    <?= view('site/partials/empty') ?>
<?php else: ?>
<ul class="mission-list">
    <?php foreach ($missions as $mission): ?>
    <li class="mission-item">
        <h3 class="h5 mb-1"><a href="<?= esc(mission_url($mission), 'attr') ?>"><?= esc($mission['name']) ?></a></h3>
        <?php
        $place = implode(', ', array_filter([$mission['city'] ?? null, $mission['location_country'] ?? null]));
        if ($showCoverage) {
            $place .= ' · ' . (($mission['coverage'] ?? '') === 'non_resident'
                ? (empty($mission['location_country']) ? lang('Public.nonResidentNoPlace') : lang('Public.nonResident', [$mission['location_country']]))
                : lang('Public.resident'));
        }
        ?>
        <p class="mb-0 text-muted"><?= esc($place) ?></p>
    </li>
    <?php endforeach ?>
</ul>
<?php endif ?>
