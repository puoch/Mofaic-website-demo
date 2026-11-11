/*
 * Hover panels in the main navigation (partials/primary_nav.php).
 *
 * With a mouse a panel opens on hover (CSS). This script shows the small
 * arrow button beside each item so the panel also opens by click, touch or
 * keyboard, and makes the panels easy to dismiss (WCAG 1.4.13):
 *  - Escape closes an open panel (focus goes back to its arrow) and hides a
 *    hovered panel until the pointer leaves the item;
 *  - a click outside, or moving focus out of the item, closes it;
 *  - opening one panel closes the others.
 */
(function () {
    'use strict';

    var items = Array.prototype.slice.call(document.querySelectorAll('.site-nav .has-panel'));

    if (items.length === 0) {
        return;
    }

    function toggleOf(item) {
        return item.querySelector('.nav-panel-toggle');
    }

    function setOpen(item, open) {
        item.classList.toggle('is-open', open);
        toggleOf(item).setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeAll(except) {
        items.forEach(function (item) {
            if (item !== except && item.classList.contains('is-open')) {
                setOpen(item, false);
            }
        });
    }

    items.forEach(function (item) {
        var toggle = toggleOf(item);

        toggle.hidden = false;

        toggle.addEventListener('click', function () {
            var open = !item.classList.contains('is-open');
            closeAll(item);
            setOpen(item, open);
            item.classList.remove('is-dismissed');
        });

        item.addEventListener('mouseenter', function () {
            closeAll(item);
        });

        item.addEventListener('mouseleave', function () {
            item.classList.remove('is-dismissed');
        });

        item.addEventListener('focusout', function (event) {
            if (event.relatedTarget && !item.contains(event.relatedTarget)) {
                setOpen(item, false);
            }
        });

        item.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            if (item.classList.contains('is-open')) {
                setOpen(item, false);
                toggle.focus();
            }
            item.classList.add('is-dismissed');
        });
    });

    // Escape also hides a panel shown by hover while focus is elsewhere.
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            items.forEach(function (item) {
                if (item.matches(':hover')) {
                    item.classList.add('is-dismissed');
                }
            });
        }
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.site-nav .has-panel')) {
            closeAll(null);
        }
    });
}());
