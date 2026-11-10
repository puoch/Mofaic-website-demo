/*
 * Search button in the main navigation. Without JavaScript the button is a
 * link to the search page. With it, the button opens the search dialog as a
 * bar under the navigation: focus moves into the field, Escape, the close
 * button or a click outside the bar closes it, and focus goes back to the
 * button.
 */
(function () {
    'use strict';

    var toggle = document.querySelector('.site-search-toggle');
    var dialog = document.getElementById('site-search');

    if (!toggle || !dialog || typeof dialog.showModal !== 'function') {
        return;
    }

    var input = dialog.querySelector('input[type="search"]');
    var close = dialog.querySelector('[data-search-close]');

    toggle.setAttribute('role', 'button');
    toggle.setAttribute('aria-haspopup', 'dialog');
    toggle.setAttribute('aria-expanded', 'false');

    function open(event) {
        event.preventDefault();
        dialog.showModal();
        toggle.setAttribute('aria-expanded', 'true');
        input.focus();
    }

    toggle.addEventListener('click', open);

    // A link does not react to the space bar; a button does.
    toggle.addEventListener('keydown', function (event) {
        if (event.key === ' ') {
            open(event);
        }
    });

    // Escape closes the bar at once, even when the field has text in it
    // (a search field would otherwise use the first Escape to clear itself).
    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            dialog.close();
        }
    });

    close.addEventListener('click', function () {
        dialog.close();
    });

    // A click on the dimmed area outside the bar closes it.
    dialog.addEventListener('click', function (event) {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    dialog.addEventListener('close', function () {
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    });
})();
