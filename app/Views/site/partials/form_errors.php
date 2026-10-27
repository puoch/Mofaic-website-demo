<?php
/**
 * @var array<string, string> $errors
 */
?>
<?php if ($errors !== []): ?>
<div class="alert alert-danger" role="alert" tabindex="-1" id="form-errors" data-testid="form-errors">
    <p class="mb-1"><?= esc(lang('Forms.errorsHeading')) ?></p>
    <ul class="mb-0">
        <?php foreach ($errors as $field => $message): ?>
        <li><?php if ($field !== 'form'): ?><a href="#field-<?= esc($field, 'attr') ?>"><?= esc($message) ?></a><?php else: ?><?= esc($message) ?><?php endif ?></li>
        <?php endforeach ?>
    </ul>
</div>
<?php endif ?>
