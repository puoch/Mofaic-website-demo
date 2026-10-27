<?php
/**
 * One labelled form field with its error and help text.
 *
 * @var string                     $name
 * @var string                     $label
 * @var string                     $type     text, email, tel, textarea or select
 * @var bool                       $required
 * @var array<string, string>      $values
 * @var array<string, string>      $errors
 * @var array<string, string>|null $options  for select
 * @var string|null                $help
 * @var string|null                $autocomplete
 * @var int|null                   $maxlength
 */
$id        = 'field-' . $name;
$value     = $values[$name] ?? '';
$error     = $errors[$name] ?? null;
$help    ??= null;
$options ??= [];
$autocomplete ??= null;
$maxlength ??= null;
$describedBy = trim(($help !== null ? $id . '-help ' : '') . ($error !== null ? $id . '-error' : ''));
$attrs = ' id="' . esc($id, 'attr') . '" name="' . esc($name, 'attr') . '"'
    . ' class="' . ($type === 'select' ? 'form-select' : 'form-control') . ($error !== null ? ' is-invalid' : '') . '"'
    . ($required ? ' required aria-required="true"' : '')
    . ($error !== null ? ' aria-invalid="true"' : '')
    . ($describedBy !== '' ? ' aria-describedby="' . esc($describedBy, 'attr') . '"' : '')
    . ($autocomplete !== null ? ' autocomplete="' . esc($autocomplete, 'attr') . '"' : '')
    . ($maxlength !== null ? ' maxlength="' . (int) $maxlength . '"' : '');
?>
<div class="mb-3">
    <label class="form-label" for="<?= esc($id, 'attr') ?>"><?= esc($label) ?>
        <span class="field-need">(<?= esc($required ? lang('Forms.required') : lang('Forms.optional')) ?>)</span></label>
    <?php if ($type === 'textarea'): ?>
    <textarea rows="5"<?= $attrs ?>><?= esc($value) ?></textarea>
    <?php elseif ($type === 'select'): ?>
    <select<?= $attrs ?>>
        <option value=""><?= esc($placeholder ?? '') ?></option>
        <?php foreach ($options as $key => $optionLabel): ?>
        <option value="<?= esc((string) $key, 'attr') ?>"<?= (string) $key === $value ? ' selected' : '' ?>><?= esc($optionLabel) ?></option>
        <?php endforeach ?>
    </select>
    <?php else: ?>
    <input type="<?= esc($type, 'attr') ?>" value="<?= esc($value, 'attr') ?>"<?= $attrs ?>>
    <?php endif ?>
    <?php if ($help !== null): ?><div class="form-text" id="<?= esc($id, 'attr') ?>-help"><?= esc($help) ?></div><?php endif ?>
    <?php if ($error !== null): ?><div class="invalid-feedback" id="<?= esc($id, 'attr') ?>-error"><?= esc($error) ?></div><?php endif ?>
</div>
