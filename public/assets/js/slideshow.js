/*
 * Featured slideshow on the home page (site/home). Without this script only
 * the first slide shows. Turns on the previous/next and pause controls and
 * moves on by itself every 7 seconds (sponsor's request) until the visitor
 * presses pause (WCAG 2.2.2); it also waits while keyboard focus is inside
 * it. The left and right arrow keys change slides when focus is inside it.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-slideshow]');
    if (!root) {
        return;
    }

    var slides   = Array.prototype.slice.call(root.querySelectorAll('[data-slide]'));
    var controls = root.querySelector('[data-slide-controls]');
    if (slides.length < 2 || !controls) {
        return;
    }

    var pauseBtn = controls.querySelector('[data-slide-pause]');
    var count    = controls.querySelector('[data-slide-count]');
    var current  = 0;
    var timer    = null;
    var stopped  = false;
    var focused  = false;

    function show(index) {
        slides[current].hidden = true;
        current = (index + slides.length) % slides.length;
        slides[current].hidden = false;
        count.textContent = (current + 1) + ' / ' + slides.length;
    }

    function schedule() {
        window.clearInterval(timer);
        timer = null;
        var playing = !stopped && !focused;
        if (playing) {
            timer = window.setInterval(function () { show(current + 1); }, 7000);
        }
        // Announce slide changes only when they are the visitor's own.
        root.setAttribute('aria-live', stopped ? 'polite' : 'off');
    }

    function setStopped(value) {
        stopped = value;
        pauseBtn.setAttribute('aria-label', pauseBtn.getAttribute(stopped ? 'data-label-play' : 'data-label-pause'));
        pauseBtn.firstElementChild.innerHTML = stopped ? '&#9654;' : '&#10074;&#10074;';
        schedule();
    }

    // A manual change restarts the 7-second wait.
    controls.querySelector('[data-slide-prev]').addEventListener('click', function () { show(current - 1); schedule(); });
    controls.querySelector('[data-slide-next]').addEventListener('click', function () { show(current + 1); schedule(); });
    pauseBtn.addEventListener('click', function () { setStopped(!stopped); });

    root.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            var forward = (event.key === 'ArrowRight') !== (document.documentElement.dir === 'rtl');
            show(current + (forward ? 1 : -1));
            event.preventDefault();
        }
    });
    // Only keyboard focus holds it; a mouse click on the arrows does not.
    root.addEventListener('focusin', function (event) {
        focused = event.target.matches(':focus-visible');
        schedule();
    });
    root.addEventListener('focusout', function (event) {
        if (!root.contains(event.relatedTarget)) {
            focused = false;
            schedule();
        }
    });

    controls.hidden = false;
    count.textContent = '1 / ' + slides.length;
    setStopped(stopped);
}());
