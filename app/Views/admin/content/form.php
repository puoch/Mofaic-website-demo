<?php
/**
 * Generic editing form for any type in Config\ContentTypes.
 *
 * @var string                    $type
 * @var array<string, mixed>      $definition
 * @var array<string, mixed>|null $record
 * @var array<string, mixed>      $values
 * @var array<string, string>     $errors
 * @var array<string, array>      $options
 * @var bool                      $canEdit
 */
use App\Libraries\MediaService;

$isNew    = $record === null;
$isSystem = ! empty($record['is_system']);
$action   = $isNew ? site_url('admin/content/' . $type) : site_url('admin/content/' . $type . '/' . $record['id']);
$title    = $isNew ? 'New ' . strtolower($definition['singular']) : (string) ($record[$definition['titleField']] ?? $definition['singular']);
if ($type === 'site_blocks' && ! $isNew) {
    $title = config('ContentTypes')->siteBlocks[$record['block_key']] ?? $title;
}
?>
<?= $this->extend('layouts/admin') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<p><a href="<?= esc(site_url('admin/content/' . $type), 'attr') ?>">← <?= esc($definition['label']) ?></a></p>
<h1 class="page-title"><?= esc($title) ?></h1>

<?php if (! $isNew && ! empty($definition['status'])): ?>
<div class="status-panel d-flex flex-wrap align-items-center gap-2 mb-3" data-testid="status-panel">
    <span class="badge <?= $record['status'] === 'published' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= esc($record['status'] === 'published' ? 'Published' : 'Draft') ?></span>
    <?php if ($publicUrl !== null): ?><a href="<?= esc($publicUrl, 'attr') ?>">View on the website</a><?php endif ?>
    <?php if ($canEdit): ?>
    <form method="post" action="<?= esc($action . ($record['status'] === 'published' ? '/unpublish' : '/publish'), 'attr') ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm <?= $record['status'] === 'published' ? 'btn-outline-secondary' : 'btn-success' ?>" type="submit"><?= $record['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
    </form>
    <?php endif ?>
    <?php if (! empty($definition['revisions'])): ?><a href="<?= esc($action . '/revisions', 'attr') ?>">Revision history</a><?php endif ?>
</div>
<?php endif ?>

<?php if (! $canEdit): ?>
<div class="alert alert-info" role="status">You can view this item but not change it.</div>
<?php endif ?>
<?php if (isset($errors['_form'])): ?>
<div class="alert alert-danger" role="alert"><?= esc($errors['_form']) ?></div>
<?php elseif ($errors !== []): ?>
<div class="alert alert-danger" role="alert">Check the fields marked below.</div>
<?php endif ?>

<form method="post" action="<?= esc($action, 'attr') ?>" novalidate data-testid="content-form">
    <?= csrf_field() ?>
    <fieldset<?= $canEdit ? '' : ' disabled' ?>>
    <?php foreach ($definition['fields'] as $name => $field): ?>
        <?php
        $id       = 'field-' . $name;
        $value    = $values[$name] ?? '';
        $locked   = $isSystem && ! empty($field['lockedForSystem']);
        $invalid  = isset($errors[$name]) ? ' is-invalid' : '';
        $describe = trim((isset($field['help']) ? $id . '-help ' : '') . ($invalid !== '' ? $id . '-error' : ''));
        $aria     = $describe === '' ? '' : ' aria-describedby="' . esc($describe, 'attr') . '"';
        $disabled = $locked ? ' disabled' : '';
        ?>
        <div class="mb-3">
            <?php if ($field['type'] === 'checkbox'): ?>
            <div class="form-check">
                <input class="form-check-input<?= $invalid ?>" type="checkbox" id="<?= $id ?>" name="<?= esc($name, 'attr') ?>" value="1"<?= ! empty($value) ? ' checked' : '' ?><?= $aria ?><?= $disabled ?>>
                <label class="form-check-label" for="<?= $id ?>"><?= esc($field['label']) ?></label>
            </div>
            <?php else: ?>
            <label class="form-label" for="<?= $id ?>"><?= esc($field['label']) ?><?= str_contains($field['rules'] ?? '', 'required') ? ' <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">(required)</span>' : '' ?></label>
                <?php if (in_array($field['type'], ['select', 'department'], true)): ?>
                <select class="form-select<?= $invalid ?>" id="<?= $id ?>" name="<?= esc($name, 'attr') ?>"<?= $aria ?><?= $disabled ?>>
                    <option value="">—</option>
                    <?php foreach ($options[$name] ?? [] as $optionValue => $label): ?>
                    <option value="<?= esc((string) $optionValue, 'attr') ?>"<?= (string) $optionValue === (string) $value ? ' selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach ?>
                    <?php if ($locked && $value !== '' && ! isset($options[$name][$value])): ?>
                    <option value="<?= esc((string) $value, 'attr') ?>" selected><?= esc((string) $value) ?></option>
                    <?php endif ?>
                </select>
                <?php elseif (in_array($field['type'], ['textarea', 'markdown', 'links'], true)): ?>
                <textarea class="form-control<?= $field['type'] === 'markdown' ? ' font-monospace' : '' ?><?= $invalid ?>" id="<?= $id ?>" name="<?= esc($name, 'attr') ?>" rows="<?= $field['type'] === 'markdown' ? 14 : 4 ?>"<?= $aria ?><?= $disabled ?>><?= esc((string) $value) ?></textarea>
                <?php if ($field['type'] === 'markdown'): ?>
                <div class="form-text">Formatting: a blank line starts a new paragraph; <code>## Heading</code>; <code>**bold**</code>; <code>- list item</code>; <code>[link text](https://address)</code>.</div>
                <?php endif ?>
                <?php else: ?>
                <input class="form-control<?= $invalid ?>" type="<?= match ($field['type']) { 'email' => 'email', 'url' => 'url', 'number' => 'number', default => 'text' } ?>" id="<?= $id ?>" name="<?= esc($name, 'attr') ?>" value="<?= esc((string) $value, 'attr') ?>"<?= $aria ?><?= $disabled ?>>
                <?php endif ?>
            <?php endif ?>
            <?php if (isset($field['help'])): ?><div class="form-text" id="<?= $id ?>-help"><?= esc($field['help']) ?></div><?php endif ?>
            <?php if ($locked): ?><div class="form-text">Fixed by the approved site structure.</div><?php endif ?>
            <?php if ($invalid !== ''): ?><div class="invalid-feedback" id="<?= $id ?>-error"><?= esc($errors[$name]) ?></div><?php endif ?>
        </div>
    <?php endforeach ?>
    <button class="btn btn-primary" type="submit">Save</button>
    </fieldset>
</form>

<?php if (! $isNew && $canEdit && empty($definition['status']) && empty($definition['fixedRows']) && empty($record['is_system'])): ?>
<form class="mt-3" method="post" action="<?= esc($action . '/delete', 'attr') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
</form>
<?php endif ?>

<?php if (! $isNew && $type === 'missions'): ?>
<section class="mt-5" id="coverage" aria-labelledby="coverage-heading">
    <h2 id="coverage-heading" class="h4">Countries served</h2>
    <p class="text-muted">Record every country this mission serves. "Non-resident" means it serves the country without being located there.</p>
    <?php if ($coverage === []): ?>
    <p class="section-pending">No countries recorded.</p>
    <?php else: ?>
    <ul class="list-unstyled" data-testid="coverage-list">
        <?php foreach ($coverage as $country): ?>
        <li class="d-flex align-items-center gap-2 mb-1">
            <?= esc($country['name']) ?> <span class="text-muted">(<?= $country['coverage'] === 'non_resident' ? 'non-resident' : 'resident' ?>)</span>
            <?php if ($canEdit): ?>
            <form method="post" action="<?= esc($action . '/coverage/' . $country['id'] . '/remove', 'attr') ?>">
                <?= csrf_field() ?><button class="btn btn-link btn-sm p-0" type="submit">Remove<span class="visually-hidden"> <?= esc($country['name']) ?></span></button>
            </form>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
    <?php if ($canEdit): ?>
    <form class="row g-2 align-items-end" method="post" action="<?= esc($action . '/coverage', 'attr') ?>">
        <?= csrf_field() ?>
        <div class="col-auto">
            <label class="form-label" for="coverage-country">Country</label>
            <select class="form-select" id="coverage-country" name="country_id" required>
                <option value="">—</option>
                <?php foreach ($countries as $countryId => $name): ?><option value="<?= esc((string) $countryId, 'attr') ?>"><?= esc($name) ?></option><?php endforeach ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label" for="coverage-type">Coverage</label>
            <select class="form-select" id="coverage-type" name="coverage">
                <option value="resident">Resident</option>
                <option value="non_resident">Non-resident</option>
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Add country</button></div>
    </form>
    <?php if ($countries === []): ?><p class="form-text">Add countries first under Content › Countries.</p><?php endif ?>
    <?php endif ?>
</section>
<?php endif ?>

<?php if (! $isNew && ! empty($definition['media'])): ?>
<section class="mt-5" id="media" aria-labelledby="media-heading">
    <h2 id="media-heading" class="h4">Files and media</h2>
    <?php if ($media === []): ?>
    <p class="section-pending">No files yet.</p>
    <?php else: ?>
    <ul class="list-unstyled" data-testid="media-list">
        <?php foreach ($media as $item): ?>
        <li class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="badge text-bg-light"><?= esc($mediaKinds[$item['kind']]['label'] ?? $item['kind']) ?></span>
            <a href="<?= esc(MediaService::url($item), 'attr') ?>"><?= esc($item['title']) ?></a>
            <?php if ($item['source'] === 'external'): ?><span class="text-muted small">external link</span><?php endif ?>
            <?php if ($canEdit): ?>
            <form method="post" action="<?= esc($action . '/media/' . $item['link_id'] . '/remove', 'attr') ?>">
                <?= csrf_field() ?><button class="btn btn-link btn-sm p-0" type="submit">Remove<span class="visually-hidden"> <?= esc($item['title']) ?></span></button>
            </form>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>

    <?php if ($canEdit): ?>
    <form class="border rounded p-3" method="post" action="<?= esc($action . '/media', 'attr') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="media-kind">Kind</label>
                <select class="form-select" id="media-kind" name="kind" required>
                    <?php foreach ($mediaKinds as $kind => $rules): ?>
                    <option value="<?= esc($kind, 'attr') ?>"><?= esc($rules['label']) ?> (<?= esc(strtoupper(implode(', ', $rules['extensions']))) ?>, up to <?= esc(round($rules['maxKb'] / 1024)) ?> MB)</option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="media-title">Title</label>
                <input class="form-control" id="media-title" name="title" maxlength="255" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="media-alt">Image description</label>
                <input class="form-control" id="media-alt" name="alt_text" maxlength="500" aria-describedby="media-alt-help">
                <div class="form-text" id="media-alt-help">Required for images. Describe what the image shows.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="media-file">File</label>
                <input class="form-control" type="file" id="media-file" name="file">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="media-url">Or link to audio or video on an official channel</label>
                <input class="form-control" type="url" id="media-url" name="external_url" placeholder="https://">
            </div>
        </div>
        <button class="btn btn-outline-primary mt-3" type="submit">Add file</button>
    </form>
    <?php endif ?>
</section>
<?php endif ?>
<?= $this->endSection() ?>
