<?php
/**
 * Approved top-level navigation (brief section 2). Items and order come from
 * Config\Navigation; do not hard-code menu items here.
 *
 * Every item is a plain link to its page. Every item except HOME also has a
 * hover panel listing its sub pages (2026-11-11, project owner), filled by
 * nav_panel(). With a mouse the panel opens on hover. The small arrow button
 * beside the item opens it by click, touch or keyboard; Escape closes it
 * (assets/js/site-nav.js). Without JavaScript the arrow stays hidden and the
 * item still opens its page, whose buttons lead to the same sub pages.
 *
 * The search button after the last item is a link to the search page. With
 * JavaScript (assets/js/site-search.js) it opens the search dialog below
 * instead: focus moves into the field, Escape or the close button closes it
 * and focus returns to the button.
 */
$items = primary_navigation();
?>
<nav class="navbar navbar-expand-lg site-nav" aria-label="<?= esc(lang('Site.mainNavigation'), 'attr') ?>" data-testid="primary-nav">
    <div class="container-xl">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#primary-nav-items"
                aria-controls="primary-nav-items" aria-expanded="false" aria-label="<?= esc(lang('Site.toggleNavigation'), 'attr') ?>">
            <?php // Drawn inline: the site's Content-Security-Policy (img-src 'self') stops
                  // phone browsers from showing Bootstrap's data: image icon. ?>
            <svg class="site-menu-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 30" width="30" height="30" aria-hidden="true" focusable="false">
                <path d="M4 7h22M4 15h22M4 23h22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
        </button>
        <div class="collapse navbar-collapse" id="primary-nav-items">
            <ul class="navbar-nav">
                <?php foreach ($items as $item): ?>
                    <?php
                    $active = nav_is_active($item['path']);
                    $label  = lang('Site.nav.' . $item['key']);
                    $panel  = nav_panel($item['key']);
                    $panelId = 'nav-panel-' . $item['key'];
                    ?>
                    <li class="nav-item<?= $panel !== [] ? ' has-panel' : '' ?>">
                        <?php if ($panel !== []): ?><div class="nav-item-head"><?php endif ?>
                        <a class="nav-link<?= $active ? ' active' : '' ?>" data-nav-key="<?= esc($item['key'], 'attr') ?>"
                           href="<?= esc(locale_url($item['path']), 'attr') ?>"<?= $active ? ' aria-current="page"' : '' ?>><?= esc($label) ?></a>
                        <?php if ($panel !== []): ?>
                        <button class="nav-panel-toggle" type="button" aria-expanded="false" aria-controls="<?= esc($panelId, 'attr') ?>" hidden>
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
                                <path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="visually-hidden"><?= esc(lang('Site.navShowPanel', [$label])) ?></span>
                        </button>
                        </div>
                        <div class="nav-panel" id="<?= esc($panelId, 'attr') ?>" data-testid="nav-panel-<?= esc($item['key'], 'attr') ?>">
                            <div class="container-xl nav-panel-inner">
                                <div class="nav-panel-intro">
                                    <p class="nav-panel-title"><?= esc($label) ?></p>
                                    <a class="nav-panel-more" href="<?= esc(locale_url($item['path']), 'attr') ?>"><?= esc(lang('Site.navPanelOpen', [$label])) ?></a>
                                </div>
                                <div class="nav-panel-groups">
                                    <?php foreach ($panel as $group): ?>
                                    <div class="nav-panel-group">
                                        <?php if ($group['title'] !== null): ?>
                                        <p class="nav-panel-heading"><a href="<?= esc(locale_url($group['path']), 'attr') ?>"><?= esc($group['title']) ?></a></p>
                                        <?php endif ?>
                                        <ul class="nav-panel-links">
                                            <?php foreach ($group['links'] as $link): ?>
                                            <li><a href="<?= esc(locale_url($link['path']), 'attr') ?>"><?= esc($link['label']) ?></a></li>
                                            <?php endforeach ?>
                                        </ul>
                                    </div>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </div>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
        <a class="site-search-toggle<?= nav_is_active('search') ? ' active' : '' ?>" href="<?= esc(locale_url('search'), 'attr') ?>"
           aria-controls="site-search" data-testid="search-toggle">
            <svg class="site-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                <circle cx="10.5" cy="10.5" r="6.5" fill="none" stroke="currentColor" stroke-width="2.25"/>
                <path d="M15.5 15.5 21 21" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"/>
            </svg>
            <span class="visually-hidden"><?= esc(lang('Site.openSearch')) ?></span>
        </a>
    </div>
</nav>
<dialog class="site-search-dialog" id="site-search" aria-labelledby="site-search-title" data-testid="search-dialog">
    <div class="site-search-bar">
    <form class="site-search-form container-xl" role="search" method="get" action="<?= esc(locale_url('search'), 'attr') ?>">
        <h2 class="visually-hidden" id="site-search-title"><?= esc(lang('Site.searchLabel')) ?></h2>
        <label class="visually-hidden" for="site-search-input"><?= esc(lang('Site.searchLabel')) ?></label>
        <input class="form-control site-search-input" type="search" id="site-search-input" name="q" maxlength="100" required
               placeholder="<?= esc(lang('Site.searchPlaceholder'), 'attr') ?>" autocomplete="off">
        <button class="btn btn-primary site-search-submit" type="submit"><?= esc(lang('Site.search')) ?></button>
        <button class="site-search-close" type="button" data-search-close aria-label="<?= esc(lang('Site.closeSearch'), 'attr') ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                <path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"/>
            </svg>
        </button>
    </form>
    </div>
</dialog>
