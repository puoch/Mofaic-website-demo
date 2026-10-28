/*
 * Country search on the missions directory (site/missions/_mission_cards).
 * Shows only the cards whose country, city or name contain the typed text,
 * or that cover a matching country from elsewhere (the card then says which
 * countries it also covers), and announces the count to screen readers.
 */
(function () {
    'use strict';

    var box = document.querySelector('[data-mission-filter]');
    if (!box) {
        return;
    }

    var input  = box.querySelector('input');
    var status = box.querySelector('[role="status"]');
    var empty  = box.querySelector('[data-mission-filter-empty]');
    var items  = Array.prototype.slice.call(document.querySelectorAll('[data-search]'));

    function normalise(text) {
        return (text || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
    }

    function apply() {
        var query = normalise(input.value);
        var shown = 0;

        items.forEach(function (item) {
            var direct  = query === '' || normalise(item.getAttribute('data-search')).indexOf(query) !== -1;
            var covered = query !== '' && !direct && normalise(item.getAttribute('data-covers')).indexOf(query) !== -1;
            var note    = item.querySelector('[data-covers-note]');

            item.hidden = !(direct || covered);
            if (note) {
                note.hidden = !covered;
            }
            shown += direct || covered ? 1 : 0;
        });

        empty.hidden = shown !== 0;
        status.textContent = query === '' ? '' : status.getAttribute('data-template')
            .replace('__SHOWN__', String(shown))
            .replace('__TOTAL__', String(items.length));
    }

    input.addEventListener('input', apply);
    box.hidden = false;
}());
