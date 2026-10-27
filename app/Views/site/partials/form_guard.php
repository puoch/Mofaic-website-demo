<?php
/**
 * CSRF token, signed time stamp and the hidden field people leave empty
 * (PD-11). Print inside every public form.
 *
 * @var string $form
 */
$guard = service('formGuard')->fields($form);
$hp    = config('Submissions')->honeypotField;
?>
<?= csrf_field() ?>
<input type="hidden" name="form_token" value="<?= esc($guard['form_token'], 'attr') ?>">
<div class="form-hp" aria-hidden="true">
    <label for="<?= esc($form . '-' . $hp, 'attr') ?>"><?= esc(lang('Forms.honeypotLabel')) ?></label>
    <input type="text" id="<?= esc($form . '-' . $hp, 'attr') ?>" name="<?= esc($hp, 'attr') ?>" value="" tabindex="-1" autocomplete="off">
</div>
