<?php
/**
 * Attachments of a page, article, album or archive item, grouped by kind.
 *
 * @var list<array<string, mixed>> $media
 */
use App\Libraries\MediaService;

$documents = array_filter($media, static fn ($m) => $m['kind'] === 'document');
$images    = array_filter($media, static fn ($m) => $m['kind'] === 'image');
$av        = array_filter($media, static fn ($m) => in_array($m['kind'], ['audio', 'video'], true));
?>
<?php if ($images !== []): ?>
<?php /* Same layout as the mission photos: the first image large, the rest smaller below it, each in its own proportions and opening full size. */ ?>
<section class="media-section" aria-labelledby="media-images" data-testid="photos">
    <h2 id="media-images" class="h5"><?= esc(lang('Public.images')) ?></h2>
    <ul class="photo-gallery">
        <?php foreach (array_values($images) as $i => $image): ?>
        <li class="<?= $i === 0 ? 'photo-gallery-main' : 'photo-gallery-thumb' ?>">
            <figure class="media-figure">
                <a href="<?= esc(MediaService::url($image), 'attr') ?>">
                    <img src="<?= esc(MediaService::url($image), 'attr') ?>" alt="<?= esc((string) $image['alt_text'], 'attr') ?>"<?= $i === 0 ? '' : ' loading="lazy"' ?>>
                </a>
                <?php if ($image['title'] !== ''): ?><figcaption><?= esc($image['title']) ?></figcaption><?php endif ?>
            </figure>
        </li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<?php if ($av !== []): ?>
<section class="media-section" aria-labelledby="media-av">
    <h2 id="media-av" class="h5"><?= esc(lang('Public.audioVideo')) ?></h2>
    <ul class="media-list">
        <?php foreach ($av as $item): ?>
        <li>
            <p class="mb-1"><?= esc($item['title']) ?></p>
            <?php if ($item['source'] === 'external'): ?>
            <a href="<?= esc(MediaService::url($item), 'attr') ?>" rel="noopener" target="_blank"><?= esc(lang('Public.watchListen')) ?> <span class="visually-hidden"><?= esc(lang('Public.opensExternal')) ?></span></a>
            <?php elseif ($item['kind'] === 'audio'): ?>
            <audio controls preload="none" src="<?= esc(MediaService::url($item), 'attr') ?>"></audio>
            <?php else: ?>
            <video controls preload="none" src="<?= esc(MediaService::url($item), 'attr') ?>"></video>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<?php if ($documents !== []): ?>
<section class="media-section" aria-labelledby="media-documents">
    <h2 id="media-documents" class="h5"><?= esc(lang('Public.downloads')) ?></h2>
    <ul class="media-list">
        <?php foreach ($documents as $document): ?>
        <li><a href="<?= esc(MediaService::url($document), 'attr') ?>"><?= esc($document['title']) ?></a> <span class="text-muted">(PDF, <?= esc(number_format(((int) $document['size']) / 1048576, 1)) ?> MB)</span></li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>
