<?php
/**
 * Approved top-level navigation (brief section 2). Items and order come from
 * Config\Navigation; do not hard-code menu items here.
 */
$items = primary_navigation();
?>
<nav class="navbar navbar-expand-lg site-nav" aria-label="<?= esc(lang('Site.mainNavigation'), 'attr') ?>" data-testid="primary-nav">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primary-nav-items"
                aria-controls="primary-nav-items" aria-expanded="false" aria-label="<?= esc(lang('Site.toggleNavigation'), 'attr') ?>">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="primary-nav-items">
            <ul class="navbar-nav">
                <?php foreach ($items as $item): ?>
                    <?php $active = nav_is_active($item['path']); ?>
                    <?php if (empty($item['children'])): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $active ? ' active' : '' ?>" data-nav-key="<?= esc($item['key'], 'attr') ?>"
                           href="<?= esc(locale_url($item['path']), 'attr') ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= esc(lang('Site.nav.' . $item['key'])) ?></a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle<?= $active ? ' active' : '' ?>" data-nav-key="<?= esc($item['key'], 'attr') ?>"
                           href="<?= esc(locale_url($item['path']), 'attr') ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= esc(lang('Site.nav.' . $item['key'])) ?></a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= esc(locale_url($item['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $item['key'])) ?></a></li>
                            <?php foreach ($item['children'] as $group): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item dropdown-group" data-nav-key="<?= esc($group['key'], 'attr') ?>" href="<?= esc(locale_url($group['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $group['key'])) ?></a></li>
                                <?php foreach ($group['children'] ?? [] as $child): ?>
                                <li><a class="dropdown-item dropdown-child" data-nav-key="<?= esc($child['key'], 'attr') ?>" href="<?= esc(locale_url($child['path']), 'attr') ?>"><?= esc(lang('Site.nav.' . $child['key'])) ?></a></li>
                                <?php endforeach ?>
                            <?php endforeach ?>
                        </ul>
                    </li>
                    <?php endif ?>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</nav>
