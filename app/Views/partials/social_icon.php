<?php
/**
 * Decorative social media icon (the link around it carries the name).
 *
 * @var string $icon facebook | youtube | x | instagram
 */
?>
<?php if ($icon === 'facebook'): ?>
<svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" d="M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z"/></svg>
<?php elseif ($icon === 'youtube'): ?>
<svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path fill="currentColor" fill-rule="evenodd" d="M6.2 4.8h11.6a4 4 0 0 1 4 4v6.4a4 4 0 0 1-4 4H6.2a4 4 0 0 1-4-4V8.8a4 4 0 0 1 4-4Zm3.6 3.6v7.2l6-3.6Z"/></svg>
<?php elseif ($icon === 'x'): ?>
<svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/></svg>
<?php elseif ($icon === 'instagram'): ?>
<svg class="social-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="21" height="21" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4.2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.4" cy="6.6" r="1.3" fill="currentColor"/></svg>
<?php endif ?>
