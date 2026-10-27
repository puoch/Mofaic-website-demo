# CDN mode and local fallback (PD-17)

Run on 2026-10-27 with `tests/browser/cdn-fallback.mjs`, Chromium from
Playwright, against `php -S` with `assets.bootstrapSource = cdn`, page `/en`.

| Case | How it was made | Bootstrap CSS | Bootstrap JS | Local files requested |
| --- | --- | --- | --- | --- |
| CDN works | CDN requests answered with the vendored files | loaded | loaded | none |
| CDN file changed | CDN requests answered with the vendored files plus one extra line | loaded (local) | loaded (local) | both |
| CDN unreachable | CDN requests aborted | loaded (local) | loaded (local) | both |

In the "changed" case Chromium reported "Failed to find a valid digest in the
'integrity' attribute" for both files, so the integrity check works.

Not checked: the real jsDelivr service. The build environment's network does
not allow cdn.jsdelivr.net, so the "CDN works" case served the files locally
under the CDN address. The integrity hashes were computed from the npm package
`bootstrap@5.3.8`, which jsDelivr serves. Before relying on CDN mode, open the site on the VPS
and confirm in the browser's network panel that both files come from
cdn.jsdelivr.net with status 200.
