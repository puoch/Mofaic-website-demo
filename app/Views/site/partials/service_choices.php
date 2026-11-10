<?php
/**
 * Full-width Services buttons (pale blue, solid blue with white text on hover and focus).
 *
 * @var array<string, string> $choices URL => label, in display order
 * @var string|null           $label   names the group for screen readers
 */
?>
<nav class="service-choices" aria-label="<?= esc($label ?? lang('Public.serviceChoices'), 'attr') ?>" data-testid="service-choices">
    <ul>
        <?php foreach ($choices as $url => $label): ?>
        <li><a class="service-choice" href="<?= esc($url, 'attr') ?>"><?= esc($label) ?></a></li>
        <?php endforeach ?>
    </ul>
</nav>
