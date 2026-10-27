/*
 * Loads the local copy of Bootstrap when the CDN copy did not arrive (CDN
 * down, blocked, or a file that failed its integrity check). Only used when
 * assets.bootstrapSource = cdn; see app/Config/Assets.php.
 */
(function () {
    // A failed stylesheet can still leave an empty link.sheet behind, so test
    // a Bootstrap rule instead: .visually-hidden is position: absolute.
    var probe = document.createElement('span');
    probe.className = 'visually-hidden';
    document.body.appendChild(probe);
    var styled = getComputedStyle(probe).position === 'absolute';
    probe.remove();

    if (!styled) {
        document.querySelectorAll('link[data-local]').forEach(function (link) {
            var local = document.createElement('link');
            local.rel = 'stylesheet';
            local.href = link.getAttribute('data-local');
            link.after(local);
        });
    }

    var script = document.querySelector('script[data-local]');
    if (script && typeof window.bootstrap === 'undefined') {
        var copy = document.createElement('script');
        copy.src = script.getAttribute('data-local');
        document.body.appendChild(copy);
    }
})();
